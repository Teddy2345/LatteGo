<?php

declare(strict_types=1);

namespace App\Application\Inventario\DTOs;

use App\Domain\Inventario\Entities\MovimientoInventario;

final readonly class MovimientoInventarioData
{
    public function __construct(
        public int $id,
        public int $productoId,
        public string $producto,
        public string $unidad,
        public string $tipo,
        public string $fecha,
        public float $cantidad,
        public ?float $litrosProcesados,
        public ?float $litrosPorUnidad,
        public ?float $precioUnitario,
        public ?float $total,
        public ?string $cliente,
        public ?string $observaciones,
        public ?string $lote = null,
    ) {
    }

    public static function desdeEntidad(MovimientoInventario $movimiento, string $producto, string $unidad): self
    {
        return new self(
            id: (int) $movimiento->id,
            productoId: $movimiento->productoId,
            producto: $producto,
            unidad: $unidad,
            tipo: $movimiento->tipo->value,
            fecha: $movimiento->fecha->format('Y-m-d'),
            cantidad: $movimiento->cantidad->valor,
            litrosProcesados: $movimiento->litrosProcesados,
            litrosPorUnidad: $movimiento->litrosPorUnidad(),
            precioUnitario: $movimiento->precioUnitario,
            total: $movimiento->total(),
            cliente: $movimiento->cliente,
            observaciones: $movimiento->observaciones,
            lote: $movimiento->lote,
        );
    }
}
