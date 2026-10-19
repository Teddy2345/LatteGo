<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Acopio\UseCases\ListarAcopiosUseCase;
use App\Application\Calidad\DTOs\CalidadData;
use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Calidad\UseCases\ListarCalidadUseCase;
use App\Application\Calidad\UseCases\RegistrarCalidadUseCase;
use App\Application\Proveedor\UseCases\ListarProveedoresUseCase;
use App\Domain\Calidad\Exceptions\CalidadException;
use App\Http\Controllers\Controller;
use App\Infrastructure\Calidad\Models\CalidadModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Calidad desde el telefono: se elige la entrega —con el nombre de quien la
 * trajo, no solo su numero—, se le toma una foto a la pantalla del LactoScan
 * y se guardan los valores, que quien analiza revisa antes de enviar.
 *
 * El reconocimiento de la foto ocurre en el telefono; aqui solo llega la
 * imagen como evidencia y los valores ya confirmados.
 */
final class CalidadMovilController extends Controller
{
    /** Las entregas que todavia esperan su analisis. */
    public function pendientes(
        Request $request,
        ListarAcopiosUseCase $listarAcopios,
        ListarCalidadUseCase $listarCalidad,
        ListarProveedoresUseCase $listarProveedores,
    ): JsonResponse {
        $this->authorize('create', CalidadModel::class);

        $analizados = array_flip(array_map(
            static fn (CalidadData $c): int => $c->acopioId,
            $listarCalidad->ejecutar(),
        ));

        $proveedores = collect($listarProveedores->ejecutar())->keyBy('id');

        $pendientes = array_values(array_filter(
            $listarAcopios->ejecutar(),
            static fn ($acopio): bool => ! isset($analizados[$acopio->id]),
        ));

        return response()->json([
            'data' => array_map(static fn ($acopio): array => [
                'id' => $acopio->id,
                'proveedor_id' => $acopio->proveedorId,
                // Sin el nombre, elegir la entrega por su numero es a ciegas.
                'proveedor' => $proveedores->get($acopio->proveedorId)?->nombre ?? 'Proveedor no encontrado',
                'fecha' => $acopio->fecha,
                'litros' => round($acopio->cantidadLitros, 2),
            ], $pendientes),
        ]);
    }

    public function store(Request $request, RegistrarCalidadUseCase $registrar): JsonResponse
    {
        $this->authorize('create', CalidadModel::class);

        $datos = $request->validate([
            'acopio_id' => ['required', 'integer', 'exists:acopios,id'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'temperatura' => ['required', 'numeric'],
            'grasa' => ['required', 'numeric'],
            'solidos_no_grasos' => ['required', 'numeric'],
            'densidad' => ['required', 'numeric'],
            'proteina' => ['required', 'numeric'],
            'lactosa' => ['required', 'numeric'],
            'sales' => ['required', 'numeric'],
            'agua_agregada' => ['required', 'numeric', 'min:0'],
            'ph' => ['required', 'numeric', 'min:0', 'max:14'],
            'prueba_alcohol_aceptada' => ['required', 'boolean'],
            'motivo_rechazo_manual' => ['nullable', 'in:acidez,otro'],
            'foto' => ['nullable', 'image', 'max:5120'],
        ]);

        // La foto de la pantalla del equipo es la evidencia del analisis.
        $fotoPath = $request->hasFile('foto')
            ? $request->file('foto')->store('calidad', 'public')
            : null;

        try {
            $calidad = $registrar->ejecutar(new RegistrarCalidadData(
                proveedorId: (int) $datos['proveedor_id'],
                acopioId: (int) $datos['acopio_id'],
                fecha: $datos['fecha'],
                temperatura: (float) $datos['temperatura'],
                grasa: (float) $datos['grasa'],
                solidosNoGrasos: (float) $datos['solidos_no_grasos'],
                densidad: (float) $datos['densidad'],
                proteina: (float) $datos['proteina'],
                lactosa: (float) $datos['lactosa'],
                sales: (float) $datos['sales'],
                aguaAgregada: (float) $datos['agua_agregada'],
                ph: (float) $datos['ph'],
                pruebaAlcoholAceptada: (bool) $datos['prueba_alcohol_aceptada'],
                motivoRechazoManual: $datos['motivo_rechazo_manual'] ?? null,
                fotoPath: $fotoPath,
            ));
        } catch (CalidadException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $calidad->id,
                'resultado' => $calidad->resultado,
                'motivo_rechazo' => $calidad->motivoRechazo,
                'sancion' => $calidad->sancionAplicada,
            ],
        ], 201);
    }
}
