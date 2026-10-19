<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Inventario\Exceptions\StockInsuficienteException;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontradoException;
use App\Domain\Pedidos\Repositories\PedidoRepository;

/**
 * Marca un pedido como confirmado: el equipo de ventas ya hablo con el
 * cliente y el pedido va en firme.
 *
 * Confirmar es lo que reserva la mercaderia, asi que antes se comprueba que
 * alcance lo que queda libre en el almacen: lo que hay, menos lo ya
 * comprometido en otros pedidos confirmados. El stock se descuenta de verdad
 * recien al entregar (EntregarPedidoUseCase).
 */
final class ConfirmarPedidoUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
        private readonly MovimientoInventarioRepository $movimientos,
    ) {
    }

    public function ejecutar(int $id): PedidoData
    {
        $pedido = $this->pedidos->buscarPorId($id);

        if ($pedido === null) {
            throw new PedidoNoEncontradoException("No existe un pedido con id {$id}.");
        }

        foreach ($pedido->items as $item) {
            $libre = $this->movimientos->stockDe($item->productoId)
                - $this->pedidos->cantidadReservada($item->productoId, exceptoPedidoId: $id);

            if ($item->cantidad > $libre) {
                throw new StockInsuficienteException(sprintf(
                    'No alcanza el stock para confirmar: de %s quedan %s libres y el pedido pide %s.',
                    $item->nombreProducto,
                    rtrim(rtrim(number_format(max($libre, 0), 2, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format($item->cantidad, 2, '.', ''), '0'), '.'),
                ));
            }
        }

        return PedidoData::desdeEntidad($this->pedidos->guardar($pedido->confirmar()));
    }
}
