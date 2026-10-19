<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\EntregarPedidoData;
use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontradoException;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\MetodoPago;
use App\Domain\Shared\Contracts\TransactionManager;
use DateTimeImmutable;

/**
 * Entrega un pedido y deja constancia del cobro.
 *
 * Entregar es cuando la mercaderia sale de verdad del almacen: por cada
 * producto se registra la venta, que descuenta el stock igual que una venta
 * en planta y la deja en el historial de ventas. Marcar la entrega y
 * descontar ocurren en una sola transaccion, para que no quede un pedido
 * entregado con el stock intacto.
 */
final class EntregarPedidoUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly TransactionManager $transacciones,
    ) {
    }

    public function ejecutar(EntregarPedidoData $datos): PedidoData
    {
        $pedido = $this->pedidos->buscarPorId($datos->id);

        if ($pedido === null) {
            throw new PedidoNoEncontradoException("No existe un pedido con id {$datos->id}.");
        }

        $fechaEntrega = new DateTimeImmutable($datos->fechaEntrega);

        // Falla aqui, antes de tocar el stock, si el pedido no se puede
        // entregar (sin repartidor, ya entregado, cancelado...).
        $entregado = $pedido->entregar(
            metodoPago: MetodoPago::from($datos->metodoPago),
            referenciaPago: $datos->referenciaPago,
            montoCobrado: $datos->montoCobrado,
            fechaEntrega: $fechaEntrega,
        );

        return $this->transacciones->run(function () use ($entregado, $fechaEntrega): PedidoData {
            foreach ($entregado->items as $item) {
                $this->movimientos->guardar(MovimientoInventario::venta(
                    productoId: $item->productoId,
                    fecha: $fechaEntrega,
                    cantidad: new CantidadProducto($item->cantidad),
                    precioUnitario: $item->precioUnitario,
                    cliente: $entregado->clienteNombre,
                    usuarioId: $entregado->repartidorId,
                    // A quien se le vendio viaja con la venta: el pedido de
                    // un proveedor sale a su precio, y sin esta marca en el
                    // historial pareceria un descuento suelto del vendedor.
                    tipoCliente: $entregado->tipoCliente,
                    // Atada al pedido: asi el historial de ventas sabe que
                    // esta entrega ya tiene su comprobante.
                    pedidoId: (int) $entregado->id,
                ));
            }

            return PedidoData::desdeEntidad($this->pedidos->guardar($entregado));
        });
    }
}
