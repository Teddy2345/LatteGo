<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\ProduccionData;
use App\Domain\Produccion\Entities\Produccion;
use App\Domain\Produccion\Repositories\ProduccionRepository;

final class ListarProduccionUseCase
{
    public function __construct(
        private readonly ProduccionRepository $producciones,
    ) {
    }

    /**
     * @return ProduccionData[]
     */
    public function ejecutar(): array
    {
        return array_map(
            static fn (Produccion $produccion): ProduccionData => ProduccionData::desdeEntidad($produccion),
            $this->producciones->listarTodos(),
        );
    }
}
