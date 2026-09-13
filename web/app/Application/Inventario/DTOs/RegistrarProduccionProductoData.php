<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

final readonly class RegistrarProduccionProductoData
{
    public function __construct(
        public int $productoId,
        public string $fecha,
        public float $cantidad,
        public float $litrosProcesados,
        public ?string $observaciones,
        public ?int $usuarioId,
        public ?string $requestId = null,
    ) {
    }
}
