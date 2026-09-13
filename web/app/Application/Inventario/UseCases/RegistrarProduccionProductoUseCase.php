<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\MovimientoInventarioData;
use App\Application\Inventario\DTOs\RegistrarProduccionProductoData;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use DateTimeImmutable;

/**
 * Registra una transformacion de leche en producto terminado y la ingresa
 * al almacen. El requestId enviado por la app movil hace idempotente la
 * operacion para que un reintento no infle el stock.
 */
final class RegistrarProduccionProductoUseCase
{
    public function __construct(
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly ProductoRepository $productos,
    ) {
    }

    public function ejecutar(RegistrarProduccionProductoData $datos): MovimientoInventarioData
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

        $movimiento = MovimientoInventario::produccion(
            productoId: $datos->productoId,
            fecha: new DateTimeImmutable($datos->fecha),
            cantidad: new CantidadProducto($datos->cantidad),
            litrosProcesados: $datos->litrosProcesados,
            observaciones: $datos->observaciones,
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
