<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearPedidoData;
use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Exceptions\StockInsuficienteException;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Pedidos\Entities\ItemPedido;
use App\Domain\Pedidos\Entities\Pedido;
use App\Domain\Pedidos\Exceptions\ItemPedidoInvalidoException;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use DateTimeImmutable;

/**
 * Crea un pedido desde la tienda publica. Valida que cada producto exista,
 * este activo y tenga stock disponible, pero no reserva ni descuenta nada:
 * el pedido queda pendiente hasta que ventas lo confirme (eso reserva) y se
 * entregue (eso descuenta del almacen).
 */
final class CrearPedidoUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
        private readonly ProductoRepository $productos,
        private readonly MovimientoInventarioRepository $movimientos,
    ) {
    }

    public function ejecutar(CrearPedidoData $datos): PedidoData
    {
        $items = [];

        foreach ($datos->items as $itemCarrito) {
            $producto = $this->productos->buscarPorId($itemCarrito->productoId);

            if ($producto === null || ! $producto->activo) {
                throw new ProductoNoEncontradoException("No existe un producto con id {$itemCarrito->productoId}.");
            }

            if ($producto->seVendePorUnidadEntera() && floor($itemCarrito->cantidad) !== $itemCarrito->cantidad) {
                throw new ItemPedidoInvalidoException(sprintf(
                    '%s se vende por %s: indica una cantidad sin decimales.',
                    $producto->nombre,
                    $producto->unidad,
                ));
            }

            // Disponible es lo que hay menos lo ya reservado por pedidos
            // confirmados que aun no se entregaron.
            $disponible = max(0.0, $this->movimientos->stockDe((int) $producto->id)
                - $this->pedidos->cantidadReservada((int) $producto->id));

            if ($itemCarrito->cantidad > $disponible) {
                throw new StockInsuficienteException(sprintf(
                    'Solo hay %s %s de %s en existencia.',
                    rtrim(rtrim(number_format($disponible, 2, '.', ''), '0'), '.'),
                    $producto->unidad,
                    $producto->nombre,
                ));
            }

            $items[] = ItemPedido::crear(
                productoId: (int) $producto->id,
                nombreProducto: $producto->nombre,
                cantidad: $itemCarrito->cantidad,
                precioUnitario: $producto->precioPara($datos->tipoCliente),
            );
        }

        $pedido = Pedido::crear(
            clienteNombre: $datos->clienteNombre,
            clienteTelefono: $datos->clienteTelefono,
            clienteDireccion: $datos->clienteDireccion,
            fecha: new DateTimeImmutable($datos->fecha),
            observaciones: $datos->observaciones,
            items: $items,
            usuarioId: $datos->usuarioId,
            tipoEntrega: $datos->tipoEntrega,
            tipoCliente: $datos->tipoCliente,
        );

        return PedidoData::desdeEntidad($this->pedidos->guardar($pedido));
    }
}
