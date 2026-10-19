<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Proveedor\DTOs\CrearSolicitudCambioZonaData;
use App\Application\Proveedor\UseCases\CrearSolicitudCambioZonaUseCase;
use App\Domain\Acopio\ValueObjects\SemanaPago;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Calidad\Models\CalidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use App\Presentation\Api\V1\Requests\StoreSolicitudCambioZonaMovilRequest;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lo suyo, visto desde su telefono: cuanta leche lleva entregada esta
 * semana y cuanto le representa.
 *
 * La semana de pago corre de jueves a miercoles, igual que en la planilla,
 * para que lo que ve aqui cuadre con lo que cobra. El monto es estimado
 * mientras la semana siga abierta: los acopios que aun no se sincronizan
 * pueden cambiar y las sanciones por agua se aplican al liquidar.
 */
final class MiCuentaProveedorController extends Controller
{
    public function resumen(Request $request): JsonResponse
    {
        $proveedor = $this->suFicha($request);

        $semana = SemanaPago::desde(new DateTimeImmutable('today'));

        $acopios = AcopioModel::query()
            ->where('proveedor_id', $proveedor->id)
            ->whereBetween('fecha', [$semana->inicio->format('Y-m-d'), $semana->fin->format('Y-m-d')])
            ->orderBy('fecha')
            ->get();

        $litros = round((float) $acopios->sum('cantidad_litros'), 2);
        $precio = (float) $proveedor->precio_litro;

        return response()->json([
            'data' => [
                'proveedor' => [
                    'nombre' => $proveedor->nombre,
                    'finca' => $proveedor->finca,
                    'ruta' => $proveedor->ruta?->nombre,
                    'precio_litro' => round($precio, 2),
                    'litros_prom' => (int) $proveedor->litros_prom,
                ],
                'semana' => [
                    'inicio' => $semana->inicio->format('Y-m-d'),
                    'fin' => $semana->fin->format('Y-m-d'),
                    'litros' => $litros,
                    'entregas' => $acopios->count(),
                    'monto_estimado' => round($litros * $precio, 2),
                    // Mientras la semana no cierre, lo de arriba puede moverse.
                    'cerrada' => (new DateTimeImmutable('today')) > $semana->fin,
                ],
                'entregas' => $acopios->map(static fn (AcopioModel $a): array => [
                    'id' => $a->id,
                    'fecha' => $a->fecha->format('Y-m-d'),
                    'litros' => (float) $a->cantidad_litros,
                    'estado' => (string) $a->estado,
                ])->all(),
            ],
        ]);
    }

    /**
     * Los analisis de su propia leche. El proveedor tiene derecho a saber
     * con que resultado se recibio lo que entrego: es lo que explica una
     * sancion y lo que le permite corregir antes de la siguiente entrega.
     */
    public function calidad(Request $request): JsonResponse
    {
        $proveedor = $this->suFicha($request);

        $analisis = CalidadModel::query()
            ->where('proveedor_id', $proveedor->id)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->limit(30)->get();

        return response()->json([
            'data' => $analisis->map(static fn (CalidadModel $c): array => [
                'id' => $c->id,
                'acopio_id' => $c->acopio_id,
                'fecha' => $c->fecha->format('Y-m-d'),
                'resultado' => (string) $c->resultado,
                'motivo_rechazo' => $c->motivo_rechazo,
                'sancion' => (string) $c->sancion_aplicada,
                'valores' => [
                    ['nombre' => 'Grasa', 'valor' => (float) $c->grasa, 'unidad' => '%'],
                    ['nombre' => 'Sólidos no grasos', 'valor' => (float) $c->solidos_no_grasos, 'unidad' => '%'],
                    ['nombre' => 'Proteína', 'valor' => (float) $c->proteina, 'unidad' => '%'],
                    ['nombre' => 'Lactosa', 'valor' => (float) $c->lactosa, 'unidad' => '%'],
                    ['nombre' => 'Sales', 'valor' => (float) $c->sales, 'unidad' => '%'],
                    ['nombre' => 'Densidad', 'valor' => (float) $c->densidad, 'unidad' => ''],
                    ['nombre' => 'Temperatura', 'valor' => (float) $c->temperatura, 'unidad' => '°C'],
                    ['nombre' => 'pH', 'valor' => (float) $c->ph, 'unidad' => ''],
                    ['nombre' => 'Agua agregada', 'valor' => (float) $c->agua_agregada, 'unidad' => '%'],
                ],
            ])->all(),
        ]);
    }

    /** Las zonas a las que puede pedir el traslado, sin la suya actual. */
    public function zonas(Request $request, RutaRepository $rutas): JsonResponse
    {
        $proveedor = $this->suFicha($request);

        $disponibles = array_values(array_filter(
            $rutas->listarTodas(),
            static fn ($ruta): bool => (int) $ruta->id !== (int) $proveedor->ruta_id,
        ));

        return response()->json([
            'data' => [
                'zona_actual' => $proveedor->ruta?->nombre,
                'pendiente' => app(SolicitudCambioZonaRepository::class)->existePendientePara((int) $proveedor->id),
                'zonas' => array_map(static fn ($ruta): array => [
                    'id' => (int) $ruta->id,
                    'nombre' => $ruta->nombre,
                ], $disponibles),
            ],
        ]);
    }

    /**
     * El proveedor pide mudarse de zona. Pide por si mismo: el id sale de su
     * propia ficha, nunca de lo que mande el telefono.
     */
    public function solicitarCambioZona(
        StoreSolicitudCambioZonaMovilRequest $request,
        CrearSolicitudCambioZonaUseCase $crear,
    ): JsonResponse {
        $this->authorize('create', SolicitudCambioZonaModel::class);

        $proveedor = $this->suFicha($request);

        $solicitud = $crear->ejecutar(new CrearSolicitudCambioZonaData(
            proveedorId: (int) $proveedor->id,
            rutaSolicitadaId: $request->integer('ruta_solicitada_id'),
            fechaCambio: $request->string('fecha_cambio')->toString(),
            motivo: $request->input('motivo'),
            solicitadoPor: (int) $request->user()->id,
        ));

        return response()->json([
            'data' => [
                'id' => $solicitud->id,
                'estado' => $solicitud->estado->value,
                'mensaje' => 'Tu solicitud quedó registrada. La planta la revisará.',
            ],
        ], 201);
    }

    private function suFicha(Request $request): ProveedorModel
    {
        $proveedor = ProveedorModel::query()->where('user_id', $request->user()->id)->first();

        abort_if($proveedor === null, 403, 'Esta cuenta no está ligada a un proveedor de la planta.');

        return $proveedor;
    }
}
