<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\ProductoStockData;
use App\Domain\Inventario\Entities\Producto;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\TipoProducto;

/**
 * Catalogo de productos e insumos con su stock disponible. Alimenta los
 * selectores de los formularios de planta y la pantalla de Almacen.
 */
final class ListarProductosConStockUseCase
{
    public function __construct(
        private readonly ProductoRepository $productos,
        private readonly MovimientoInventarioRepository $movimientos,
    ) {
    }

    /**
     * @return ProductoStockData[]
     */
    public function ejecutar(bool $soloActivos = true, ?TipoProducto $tipo = null): array
    {
        $stock = $this->movimientos->stockPorProducto();

        $productos = $soloActivos
            ? $this->productos->listarActivos()
            : $this->productos->listarTodos();

        if ($tipo !== null) {
            $productos = array_values(array_filter($productos, static fn (Producto $p): bool => $p->tipo === $tipo));
        }

        return array_map(
            static function (Producto $producto) use ($stock): ProductoStockData {
                $stockActual = round($stock[$producto->id] ?? 0.0, 2);

                return new ProductoStockData(
                    id: (int) $producto->id,
                    nombre: $producto->nombre,
                    tipo: $producto->tipo->value,
                    categoria: $producto->categoria,
                    unidad: $producto->unidad,
                    precioReferencia: $producto->precioReferencia,
                    stockMinimo: $producto->stockMinimo,
                    stock: $stockActual,
                    stockBajo: $producto->stockMinimo !== null && $stockActual <= $producto->stockMinimo,
                    activo: $producto->activo,
                    descripcion: $producto->descripcion,
                    fotoPath: $producto->fotoPath,
                );
            },
            $productos,
        );
    }
}
