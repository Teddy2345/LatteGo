<?php

declare(strict_types=1);

namespace App\Application\Produccion\DTOs;

final readonly class InsumoUtilizadoData
{
    public function __construct(
        public int $productoId,
        public float $cantidad,
    ) {
    }
}
