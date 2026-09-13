<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\Entities;

use App\Domain\Pedidos\Exceptions\ItemPedidoInvalidoException;

/**
 * Una linea del pedido: guarda el nombre y el precio del producto tal como
 * estaban al momento de la compra, para que el pedido no cambie si despues
 * se edita el catalogo.
 */
final readonly class ItemPedido
{
    private function __construct(
        public int $productoId,
        public string $nombreProducto,
        public float $cantidad,
        public float $precioUnitario,
    ) {
    }

    public static function crear(int $productoId, string $nombreProducto, float $cantidad, float $precioUnitario): self
    {
        if (! is_finite($cantidad) || $cantidad <= 0) {
            throw new ItemPedidoInvalidoException('La cantidad debe ser mayor a 0.');
        }

        if (! is_finite($precioUnitario) || $precioUnitario <= 0) {
            throw new ItemPedidoInvalidoException('El precio debe ser mayor a 0.');
        }

        return new self($productoId, $nombreProducto, round($cantidad, 2), round($precioUnitario, 2));
    }

    public function subtotal(): float
    {
        return round($this->cantidad * $this->precioUnitario, 2);
    }
}
