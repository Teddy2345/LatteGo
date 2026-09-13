<?php

declare(strict_types=1);

namespace App\Application\Pedidos\DTOs;

final readonly class ItemCarritoData
{
    public function __construct(
        public int $productoId,
        public float $cantidad,
    ) {
    }
}
