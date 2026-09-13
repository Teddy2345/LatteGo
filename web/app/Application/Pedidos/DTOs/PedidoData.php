<?php

declare(strict_types=1);

namespace App\Application\Pedidos\DTOs;

use App\Domain\Pedidos\Entities\Pedido;

final readonly class PedidoData
{
    /**
     * @param  ItemPedidoData[]  $items
     */
    public function __construct(
        public int $id,
        public string $clienteNombre,
        public string $clienteTelefono,
        public string $clienteDireccion,
        public string $fecha,
        public string $estado,
        public ?string $observaciones,
        public array $items,
        public float $total,
        public ?int $repartidorId = null,
        public ?string $metodoPago = null,
        public ?float $montoCobrado = null,
        public ?string $fechaEntrega = null,
    ) {
    }

    public static function desdeEntidad(Pedido $pedido): self
    {
        return new self(
            id: (int) $pedido->id,
            clienteNombre: $pedido->clienteNombre,
            clienteTelefono: $pedido->clienteTelefono,
            clienteDireccion: $pedido->clienteDireccion,
            fecha: $pedido->fecha->format('Y-m-d'),
            estado: $pedido->estado->value,
            observaciones: $pedido->observaciones,
            items: array_map(
                static fn ($item): ItemPedidoData => ItemPedidoData::desdeEntidad($item),
                $pedido->items,
            ),
            total: $pedido->total(),
            repartidorId: $pedido->repartidorId,
            metodoPago: $pedido->metodoPago?->value,
            montoCobrado: $pedido->montoCobrado,
            fechaEntrega: $pedido->fechaEntrega?->format('Y-m-d'),
        );
    }
}
