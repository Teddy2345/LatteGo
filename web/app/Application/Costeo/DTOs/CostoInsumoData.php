<?php

declare(strict_types=1);

namespace App\Application\Costeo\DTOs;

final readonly class CostoInsumoData
{
    public function __construct(
        public int $productoId,
        public string $nombre,
        public string $unidad,
        public float $cantidad,
        public ?float $costoUnitario,
        public ?float $costoTotal,
    ) {
    }
}
