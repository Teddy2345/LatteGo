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
use DateTimeImmutable;

/**
 * Registra una venta y descuenta el producto del almacen.
 *
 * No se permite vender mas de lo que hay en existencia: el stock de la
 * planta es fisico y un saldo negativo solo escondería un error de registro.
 */
final class RegistrarVentaUseCase
{
    public function __construct(
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly ProductoRepository $productos,
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

        $cantidad = new CantidadProducto($datos->cantidad);
        $disponible = $this->movimientos->stockDe($datos->productoId);

        if ($cantidad->valor > $disponible) {
            throw new StockInsuficienteException(sprintf(
                'Solo hay %s %s de %s en existencia.',
                rtrim(rtrim(number_format($disponible, 2, '.', ''), '0'), '.'),
                $producto->unidad,
                $producto->nombre,
            ));
        }

        $movimiento = MovimientoInventario::venta(
            productoId: $datos->productoId,
            fecha: new DateTimeImmutable($datos->fecha),
            cantidad: $cantidad,
            precioUnitario: $datos->precioUnitario,
            cliente: $datos->cliente,
            usuarioId: $datos->usuarioId,
            requestId: $datos->requestId,
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
