<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontradoException;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Marca un pedido como confirmado: el equipo de ventas ya hablo con el
 * cliente y el pedido va en firme. No mueve stock ni genera una venta; esa
 * integracion mas profunda (cobro, entrega, comprobante) queda para mas
 * adelante.
 */
final class ConfirmarPedidoUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
    ) {
    }

    public function ejecutar(int $id): PedidoData
    {
        $pedido = $this->pedidos->buscarPorId($id);

        if ($pedido === null) {
            throw new PedidoNoEncontradoException("No existe un pedido con id {$id}.");
        }

        return PedidoData::desdeEntidad($this->pedidos->guardar($pedido->confirmar()));
    }
}
