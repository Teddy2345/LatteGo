<?php

declare(strict_types=1);

namespace App\Application\Produccion\UseCases;

use App\Application\Produccion\DTOs\DespachoData;
use App\Domain\Produccion\Entities\Despacho;
use App\Domain\Produccion\Repositories\DespachoRepository;

final class ListarDespachoPorProduccionUseCase
{
    public function __construct(
        private readonly DespachoRepository $despachos,
    ) {
    }

    /**
     * @return DespachoData[]
     */
    public function ejecutar(int $produccionId): array
    {
        return array_map(
            static fn (Despacho $despacho): DespachoData => DespachoData::desdeEntidad($despacho),
            $this->despachos->listarPorProduccion($produccionId),
        );
    }
}
