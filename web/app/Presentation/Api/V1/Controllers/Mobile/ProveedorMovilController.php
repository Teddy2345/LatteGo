<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Acopio\DTOs\AcopioData;
use App\Application\Acopio\UseCases\ListarAcopiosPorProveedorUseCase;
use App\Application\Proveedor\DTOs\CrearSolicitudCambioZonaData;
use App\Application\Proveedor\UseCases\CrearSolicitudCambioZonaUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use App\Presentation\Api\V1\Requests\StoreSolicitudCambioZonaMovilRequest;
use Illuminate\Http\JsonResponse;

/**
 * Ficha de proveedor que consume la app movil desde la pantalla de campo:
 * datos completos, historial de litraje y la accion de solicitar traslado de
 * zona. A diferencia de mobile/catalogo (que solo expone id y nombre para no
 * dar acceso al padron completo), aqui se muestra un proveedor puntual con
 * quien el acopiador ya esta interactuando.
 */
final class ProveedorMovilController extends Controller
{
    public function show(
        int $proveedor,
        ObtenerProveedorUseCase $obtener,
        ListarAcopiosPorProveedorUseCase $historial,
        RutaRepository $rutas,
    ): JsonResponse {
        $this->authorize('viewAny', AcopioModel::class);

        $datos = $obtener->ejecutar($proveedor);
        $nombresRuta = $this->nombresPorRuta($rutas);

        return response()->json([
            'data' => [
                'id' => $datos->id,
                'nombre' => $datos->nombre,
                'cedula' => $datos->cedula,
                'telefono' => $datos->telefono,
                'finca' => $datos->finca,
                'litros_prom' => $datos->litrosProm,
                'precio_litro' => $datos->precioLitro,
                'activo' => $datos->activo,
                'ruta_id' => $datos->rutaId,
                'zona' => $datos->rutaId === null ? null : ($nombresRuta[$datos->rutaId] ?? null),
                'historial' => array_map(
                    static fn (AcopioData $acopio): array => [
                        'id' => $acopio->id,
                        'fecha' => $acopio->fecha,
                        'cantidad_litros' => $acopio->cantidadLitros,
                        'perdida_litros' => $acopio->perdidaLitros,
                        'motivo_perdida' => $acopio->motivoPerdida,
                        'estado' => $acopio->estado,
                        'ruta_id' => $acopio->rutaId,
                        'zona' => $acopio->rutaId === null ? null : ($nombresRuta[$acopio->rutaId] ?? null),
                    ],
                    $historial->ejecutar($proveedor),
                ),
            ],
        ]);
    }

    public function solicitarCambioZona(
        int $proveedor,
        StoreSolicitudCambioZonaMovilRequest $request,
        CrearSolicitudCambioZonaUseCase $crear,
    ): JsonResponse {
        $this->authorize('create', SolicitudCambioZonaModel::class);

        $solicitud = $crear->ejecutar(new CrearSolicitudCambioZonaData(
            proveedorId: $proveedor,
            rutaSolicitadaId: $request->integer('ruta_solicitada_id'),
            fechaCambio: $request->string('fecha_cambio')->toString(),
            motivo: $request->input('motivo'),
            solicitadoPor: $request->user()->id,
        ));

        return response()->json(['data' => ['id' => $solicitud->id, 'estado' => $solicitud->estado->value]], 201);
    }

    /**
     * @return array<int, string>
     */
    private function nombresPorRuta(RutaRepository $rutas): array
    {
        $nombres = [];

        foreach ($rutas->listarTodas() as $ruta) {
            $nombres[$ruta->id] = $ruta->nombre;
        }

        return $nombres;
    }
}
