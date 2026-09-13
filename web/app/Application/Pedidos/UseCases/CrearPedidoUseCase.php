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
use App\Domain\Pedidos\Repositories\PedidoRepository;
use DateTimeImmutable;

/**
 * Crea un pedido desde la tienda publica. Valida que cada producto exista,
 * este activo y tenga stock suficiente, pero no descuenta nada del almacen
 * ni cobra: el pedido solo deja constancia de lo que el cliente quiere, para
 * que el equipo de ventas lo confirme por su cuenta (cobro, entrega y
 * comprobante quedan para mas adelante).
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

            $disponible = $this->movimientos->stockDe((int) $producto->id);

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
                precioUnitario: $producto->precioReferencia,
            );
        }

        $pedido = Pedido::crear(
            clienteNombre: $datos->clienteNombre,
            clienteTelefono: $datos->clienteTelefono,
            clienteDireccion: $datos->clienteDireccion,
            fecha: new DateTimeImmutable($datos->fecha),
            observaciones: $datos->observaciones,
            items: $items,
        );

        return PedidoData::desdeEntidad($this->pedidos->guardar($pedido));
    }
}
