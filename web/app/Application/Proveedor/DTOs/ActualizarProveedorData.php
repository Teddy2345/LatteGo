<?php

declare(strict_types=1);

namespace App\Application\Proveedor\DTOs;

final readonly class ActualizarProveedorData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public ?string $telefono,
        public ?string $finca,
        public int $litrosProm,
        public float $precioLitro,
    ) {
    }
}
