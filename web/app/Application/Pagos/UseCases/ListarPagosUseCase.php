<?php

declare(strict_types=1);

namespace App\Application\Pagos\UseCases;

use App\Application\Pagos\DTOs\PagoData;
use App\Domain\Pagos\Entities\Pago;
use App\Domain\Pagos\Repositories\PagoRepository;

final class ListarPagosUseCase
{
    public function __construct(
        private readonly PagoRepository $pagos,
    ) {
    }

    /**
     * @return PagoData[]
     */
    public function ejecutar(): array
    {
        return array_map(
            static fn (Pago $pago): PagoData => PagoData::desdeEntidad($pago),
            $this->pagos->listarTodos(),
        );
    }
}
