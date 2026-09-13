<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\ProductoStockData;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Repositories\ProductoRepository;

/**
 * Obtiene un producto por id, sin calcular su stock: alimenta el formulario
 * de edicion del catalogo, que no lo necesita.
 */
final class ObtenerProductoUseCase
{
    public function __construct(
        private readonly ProductoRepository $productos,
    ) {
    }

    public function ejecutar(int $id): ProductoStockData
    {
        $producto = $this->productos->buscarPorId($id);

        if ($producto === null) {
            throw new ProductoNoEncontradoException("No existe un producto con id {$id}.");
        }

        return new ProductoStockData(
            id: (int) $producto->id,
            nombre: $producto->nombre,
            tipo: $producto->tipo->value,
            categoria: $producto->categoria,
            unidad: $producto->unidad,
            precioReferencia: $producto->precioReferencia,
            stockMinimo: $producto->stockMinimo,
            stock: 0.0,
            stockBajo: false,
            activo: $producto->activo,
            descripcion: $producto->descripcion,
            fotoPath: $producto->fotoPath,
        );
    }
}
