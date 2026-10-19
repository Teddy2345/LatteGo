<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controllers\Mobile;

use App\Application\Movilidad\DTOs\ProveedorCoberturaData;
use App\Application\Movilidad\UseCases\ObtenerCoberturaMovilidadUseCase;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Http\Controllers\Controller;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Presentation\Support\MapaReferencial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * La ruta del acopiador en el telefono: la misma informacion que ve la
 * planta en la web (mapa de referencia y proveedores que atiende), pero de
 * su propia movilidad, para que la lleve en el campo.
 */
final class MiRutaController extends Controller
{
    public function datos(
        Request $request,
        MovilidadRepository $movilidades,
        ObtenerCoberturaMovilidadUseCase $obtener,
    ): JsonResponse {
        $this->authorize('create', AcopioModel::class);

        $movilidad = $movilidades->buscarPorUsuario((int) $request->user()->id);

        if ($movilidad === null) {
            return response()->json([
                'message' => 'Todavía no tienes una movilidad asignada. Pídele a un administrador que te asigne la tuya.',
            ], 404);
        }

        $cobertura = $obtener->ejecutar((int) $movilidad->id);

        return response()->json([
            'data' => [
                'movilidad' => $cobertura->nombre,
                'tipo' => $cobertura->tipo,
                'zona' => $cobertura->rutaNombre,
                // Nombres cortos para la tira de paradas: "Achacachi", no "Achacachi, La Paz, Bolivia".
                'paradas' => array_map(static fn (string $parada): string => Str::before($parada, ','), $cobertura->paradasReferencia),
                'mapa' => MapaReferencial::embed($cobertura->paradasReferencia),
                'mapa_enlace' => MapaReferencial::enlace($cobertura->paradasReferencia),
                'mapa_pagina' => URL::temporarySignedRoute('mapa.ruta', now()->addHours(6), ['movilidad' => (int) $movilidad->id]),
                'proveedores' => array_map(
                    static fn (ProveedorCoberturaData $proveedor): array => [
                        'id' => $proveedor->id,
                        'nombre' => $proveedor->nombre,
                        'finca' => $proveedor->finca,
                        'telefono' => $proveedor->telefono,
                        'litros_prom' => $proveedor->litrosPromedio,
                    ],
                    $cobertura->proveedores,
                ),
            ],
        ]);
    }


    /**
     * La misma pagina de mapa que la web, para el WebView de la app. Se abre
     * con un enlace firmado y temporal porque el WebView no lleva la sesion
     * de la app; el enlace caduca y solo sirve para esa movilidad.
     */
    public function mapa(int $movilidad, ObtenerCoberturaMovilidadUseCase $obtener): Response
    {
        $cobertura = $obtener->ejecutar($movilidad);

        return response()->view('mapa.ruta', [
            'movilidad' => $cobertura->nombre,
            'mapa' => MapaReferencial::embed($cobertura->paradasReferencia),
        ]);
    }
}
