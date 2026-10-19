<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Domain\Produccion\Exceptions\ProduccionNoEncontradaException;
use App\Domain\Produccion\Repositories\ProduccionRepository;
use App\Domain\Produccion\Repositories\RecetaRepository;
use App\Models\User;

/**
 * La ficha de un lote: que se produjo, con que receta y que se consumio.
 * Es el papel que se archiva y se entrega a quien pida la trazabilidad del
 * lote, asi que reune en un solo lugar lo que vive repartido entre la
 * cabecera, sus productos y sus consumos.
 */
final class ObtenerFichaProduccionUseCase
{
    public function __construct(
        private readonly ProduccionRepository $producciones,
        private readonly RecetaRepository $recetas,
    ) {
    }

    /** @return array<string, mixed> */
    public function ejecutar(int $produccionId): array
    {
        $lote = $this->producciones->buscarPorId($produccionId);

        if ($lote === null) {
            throw new ProduccionNoEncontradaException("No existe una produccion con id {$produccionId}.");
        }

        $ficha = $this->recetas->fichaDeLote($produccionId);

        return [
            'id' => (int) $lote->id,
            'fecha' => $lote->fecha->format('Y-m-d'),
            'litrosProcesados' => $lote->litrosProcesados->valor,
            'observaciones' => $lote->observaciones,
            'responsable' => $lote->jefaProduccionId === null
                ? null
                : User::find($lote->jefaProduccionId)?->name,
            'productos' => $ficha['productos'],
            'consumos' => $ficha['consumos'],
        ];
    }
}
