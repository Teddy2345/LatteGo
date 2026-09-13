<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

final readonly class RegistrarEntradaInsumoData
{
    public function __construct(
        public int $productoId,
        public string $fecha,
        public float $cantidad,
        public ?string $lote,
        public ?string $motivo,
        public ?int $usuarioId,
        public ?string $requestId = null,
        public ?float $costoUnitario = null,
    ) {
    }
}
