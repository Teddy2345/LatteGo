<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\ActualizarProductoData;
use App\Domain\Inventario\Entities\Producto;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Repositories\ProductoRepository;

/**
 * Actualiza los datos editables de un producto existente. El tipo
 * (producto_terminado / insumo) no se toca aqui: cambiarlo retroactivo
 * volveria inconsistente el historial de movimientos ya registrados.
 */
final class ActualizarProductoUseCase
{
    public function __construct(
        private readonly ProductoRepository $productos,
    ) {
    }

    public function ejecutar(ActualizarProductoData $datos): Producto
    {
        $producto = $this->productos->buscarPorId($datos->id);

        if ($producto === null) {
            throw new ProductoNoEncontradoException("No existe un producto con id {$datos->id}.");
        }

        return $this->productos->guardar(new Producto(
            id: $producto->id,
            nombre: $datos->nombre,
            tipo: $producto->tipo,
            categoria: $datos->categoria,
            unidad: $datos->unidad,
            precioReferencia: $datos->precioReferencia,
            stockMinimo: $datos->stockMinimo,
            activo: $producto->activo,
            descripcion: $datos->descripcion,
            fotoPath: $datos->fotoPath,
        ));
    }
}
