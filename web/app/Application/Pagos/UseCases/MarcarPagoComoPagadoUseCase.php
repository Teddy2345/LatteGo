<?php

declare(strict_types=1);

namespace App\Application\Pagos\UseCases;

use App\Application\Pagos\DTOs\PagoData;
use App\Domain\Pagos\Exceptions\PagoNoEncontradoException;
use App\Domain\Pagos\Repositories\PagoRepository;
use DateTimeImmutable;

final class MarcarPagoComoPagadoUseCase
{
    public function __construct(
        private readonly PagoRepository $pagos,
    ) {
    }

    public function ejecutar(int $pagoId, ?string $fechaPago = null): PagoData
    {
        $pago = $this->pagos->buscarPorId($pagoId);

        if ($pago === null) {
            throw new PagoNoEncontradoException("No existe un pago con id {$pagoId}.");
        }

        $fecha = $fechaPago !== null ? new DateTimeImmutable($fechaPago) : new DateTimeImmutable();

        return PagoData::desdeEntidad($this->pagos->guardar($pago->marcarComoPagado($fecha)));
    }
}
