<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\MovimientoInventarioData;
use App\Application\Inventario\DTOs\RegistrarVentaData;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Exceptions\StockInsuficienteException;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use DateTimeImmutable;

/**
 * Registra una venta y descuenta el producto del almacen.
 *
 * No se permite vender mas de lo que hay libre: lo que hay en existencia
 * menos lo reservado por pedidos confirmados de la tienda. Si se vendiera eso,
 * al entregar el pedido el stock quedaria negativo.
 */
final class RegistrarVentaUseCase
{
    public function __construct(
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly ProductoRepository $productos,
        private readonly PedidoRepository $pedidos,
    ) {
    }

    public function ejecutar(RegistrarVentaData $datos): MovimientoInventarioData
    {
        if ($datos->requestId !== null) {
            $previo = $this->movimientos->buscarPorRequestId($datos->requestId);

            if ($previo !== null) {
                return $this->aData($previo);
            }
        }

        $producto = $this->productos->buscarPorId($datos->productoId);

        if ($producto === null) {
            throw new ProductoNoEncontradoException("No existe un producto con id {$datos->productoId}.");
        }

        $producto->exigirTipo(TipoProducto::ProductoTerminado, 'una venta');

        $cantidad = new CantidadProducto($datos->cantidad);
        $reservado = $this->pedidos->cantidadReservada($datos->productoId);
        $disponible = max(0.0, $this->movimientos->stockDe($datos->productoId) - $reservado);

        if ($cantidad->valor > $disponible) {
            $cantidadTexto = rtrim(rtrim(number_format($disponible, 2, '.', ''), '0'), '.');

            throw new StockInsuficienteException($reservado > 0
                ? sprintf('Solo quedan %s %s de %s libres: el resto está reservado para pedidos confirmados.', $cantidadTexto, $producto->unidad, $producto->nombre)
                : sprintf('Solo hay %s %s de %s en existencia.', $cantidadTexto, $producto->unidad, $producto->nombre));
        }

        $movimiento = MovimientoInventario::venta(
            productoId: $datos->productoId,
            fecha: new DateTimeImmutable($datos->fecha),
            cantidad: $cantidad,
            precioUnitario: $datos->precioUnitario,
            cliente: $datos->cliente,
            usuarioId: $datos->usuarioId,
            requestId: $datos->requestId,
            tipoCliente: $datos->tipoCliente,
        );

        return MovimientoInventarioData::desdeEntidad(
            $this->movimientos->guardar($movimiento),
            $producto->nombre,
            $producto->unidad,
        );
    }

    private function aData(MovimientoInventario $movimiento): MovimientoInventarioData
    {
        $producto = $this->productos->buscarPorId($movimiento->productoId);

        return MovimientoInventarioData::desdeEntidad(
            $movimiento,
            $producto?->nombre ?? 'Producto #'.$movimiento->productoId,
            $producto?->unidad ?? '',
        );
    }
}
