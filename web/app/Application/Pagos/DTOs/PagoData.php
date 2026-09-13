<?php

declare(strict_types=1);

namespace App\Application\Pagos\DTOs;

use App\Domain\Pagos\Entities\Pago;

final readonly class PagoData
{
    public function __construct(
        public int $id,
        public int $proveedorId,
        public string $semanaInicio,
        public string $semanaFin,
        public float $totalLitros,
        public float $precioLitro,
        public float $totalPagar,
        public ?string $fechaPago,
        public string $estado,
    ) {
    }

    public static function desdeEntidad(Pago $pago): self
    {
        return new self(
            id: $pago->id,
            proveedorId: $pago->proveedorId,
            semanaInicio: $pago->semanaInicio->format('Y-m-d'),
            semanaFin: $pago->semanaFin->format('Y-m-d'),
            totalLitros: $pago->totalLitros->valor,
            precioLitro: $pago->precioLitro,
            totalPagar: $pago->totalPagar,
            fechaPago: $pago->fechaPago?->format('Y-m-d'),
            estado: $pago->estado->value,
        );
    }
}
