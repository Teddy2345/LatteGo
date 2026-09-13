<?php

declare(strict_types=1);

namespace App\Application\Pagos\UseCases;

use App\Application\Pagos\DTOs\PagoData;
use App\Domain\Pagos\Exceptions\PagoNoEncontradoException;
use App\Domain\Pagos\Repositories\PagoRepository;

final class ObtenerPagoUseCase
{
    public function __construct(
        private readonly PagoRepository $pagos,
    ) {
    }

    public function ejecutar(int $id): PagoData
    {
        $pago = $this->pagos->buscarPorId($id);

        if ($pago === null) {
            throw new PagoNoEncontradoException("No existe un pago con id {$id}.");
        }

        return PagoData::desdeEntidad($pago);
    }
}
