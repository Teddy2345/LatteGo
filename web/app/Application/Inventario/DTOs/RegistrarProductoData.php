<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

final readonly class RegistrarProductoData
{
    public function __construct(
        public string $nombre,
        public string $tipo,
        public ?string $categoria,
        public string $unidad,
        public float $precioReferencia,
        public ?float $stockMinimo,
        public ?string $descripcion = null,
        public ?string $fotoPath = null,
    ) {
    }
}
