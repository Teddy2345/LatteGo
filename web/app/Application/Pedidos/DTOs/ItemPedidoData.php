<?php

declare(strict_types=1);

namespace App\Application\Pedidos\DTOs;

use App\Domain\Pedidos\Entities\ItemPedido;

final readonly class ItemPedidoData
{
    public function __construct(
        public int $productoId,
        public string $nombreProducto,
        public float $cantidad,
        public float $precioUnitario,
        public float $subtotal,
    ) {
    }

    public static function desdeEntidad(ItemPedido $item): self
    {
        return new self(
            productoId: $item->productoId,
            nombreProducto: $item->nombreProducto,
            cantidad: $item->cantidad,
            precioUnitario: $item->precioUnitario,
            subtotal: $item->subtotal(),
        );
    }
}
