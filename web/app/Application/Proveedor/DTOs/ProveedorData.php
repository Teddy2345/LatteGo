<?php

declare(strict_types=1);

namespace App\Application\Proveedor\DTOs;

use App\Domain\Proveedor\Entities\Proveedor;

final readonly class ProveedorData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $cedula,
        public ?string $telefono,
        public ?string $finca,
        public int $litrosProm,
        public float $precioLitro,
        public bool $activo,
        public ?int $rutaId,
    ) {
    }

    public static function desdeEntidad(Proveedor $proveedor): self
    {
        return new self(
            id: $proveedor->id,
            nombre: (string) $proveedor->nombre,
            cedula: (string) $proveedor->cedula,
            telefono: $proveedor->telefono,
            finca: $proveedor->finca,
            litrosProm: $proveedor->litrosProm->valor,
            precioLitro: $proveedor->precioLitro->valor,
            activo: $proveedor->activo,
            rutaId: $proveedor->rutaId,
        );
    }
}
