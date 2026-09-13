<?php

declare(strict_types=1);

namespace App\Application\Proveedor\DTOs;

final readonly class RegistrarProveedorData
{
    public function __construct(
        public string $nombre,
        public string $cedula,
        public ?string $telefono,
        public ?string $finca,
        public int $litrosProm,
        public float $precioLitro,
        public ?int $rutaId,
    ) {
    }
}
