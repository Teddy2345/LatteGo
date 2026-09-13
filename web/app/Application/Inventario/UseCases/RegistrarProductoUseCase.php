<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\RegistrarProductoData;
use App\Domain\Inventario\Entities\Producto;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\TipoProducto;

/**
 * Da de alta un producto terminado o un insumo en el catalogo del almacen.
 * Nace sin movimientos (stock 0): entra al almacen con una Entrada.
 */
final class RegistrarProductoUseCase
{
    public function __construct(
        private readonly ProductoRepository $productos,
    ) {
    }

    public function ejecutar(RegistrarProductoData $datos): Producto
    {
        return $this->productos->guardar(new Producto(
            id: null,
            nombre: $datos->nombre,
            tipo: TipoProducto::from($datos->tipo),
            categoria: $datos->categoria,
            unidad: $datos->unidad,
            precioReferencia: $datos->precioReferencia,
            stockMinimo: $datos->stockMinimo,
            activo: true,
            descripcion: $datos->descripcion,
            fotoPath: $datos->fotoPath,
        ));
    }
}
