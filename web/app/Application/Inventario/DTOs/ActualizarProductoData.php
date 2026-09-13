<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

final readonly class ActualizarProductoData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public ?string $categoria,
        public string $unidad,
        public float $precioReferencia,
        public ?float $stockMinimo,
        public ?string $descripcion = null,
        public ?string $fotoPath = null,
    ) {
    }
}
