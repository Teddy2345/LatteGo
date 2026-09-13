<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

final readonly class RegistrarVentaData
{
    public function __construct(
        public int $productoId,
        public string $fecha,
        public float $cantidad,
        public float $precioUnitario,
        public ?string $cliente,
        public ?int $usuarioId,
        public ?string $requestId = null,
    ) {
    }
}
