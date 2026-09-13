<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

final readonly class ProductoStockData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipo,
        public ?string $categoria,
        public string $unidad,
        public float $precioReferencia,
        public ?float $stockMinimo,
        public float $stock,
        public bool $stockBajo,
        public bool $activo,
        public ?string $descripcion = null,
        public ?string $fotoPath = null,
    ) {
    }
}
