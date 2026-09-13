<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\ProduccionData;
use App\Domain\Produccion\Exceptions\ProduccionNoEncontradaException;
use App\Domain\Produccion\Repositories\ProduccionRepository;

final class ObtenerProduccionUseCase
{
    public function __construct(
        private readonly ProduccionRepository $producciones,
    ) {
    }

    public function ejecutar(int $id): ProduccionData
    {
        $produccion = $this->producciones->buscarPorId($id);

        if ($produccion === null) {
            throw new ProduccionNoEncontradaException("No existe una produccion con id {$id}.");
        }

        return ProduccionData::desdeEntidad($produccion);
    }
}
