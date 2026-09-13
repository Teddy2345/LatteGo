<?php

declare(strict_types=1);

namespace App\Application\Inventario\UseCases;

use App\Application\Inventario\DTOs\MovimientoInventarioData;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Entities\Producto;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;

/**
 * Historial de transformaciones o de ventas, con el nombre del producto ya
 * resuelto para que la lista se pueda pintar sin consultas adicionales.
 */
final class ListarMovimientosUseCase
{
    public function __construct(
        private readonly MovimientoInventarioRepository $movimientos,
        private readonly ProductoRepository $productos,
    ) {
    }

    /**
     * @return MovimientoInventarioData[]
     */
    public function ejecutar(TipoMovimiento $tipo): array
    {
        $catalogo = [];
        foreach ($this->productos->listarTodos() as $producto) {
            /** @var Producto $producto */
            $catalogo[$producto->id] = $producto;
        }

        return array_map(
            static function (MovimientoInventario $movimiento) use ($catalogo): MovimientoInventarioData {
                $producto = $catalogo[$movimiento->productoId] ?? null;

                return MovimientoInventarioData::desdeEntidad(
                    $movimiento,
                    $producto?->nombre ?? 'Producto #'.$movimiento->productoId,
                    $producto?->unidad ?? '',
                );
            },
            $this->movimientos->listarPorTipo($tipo),
        );
    }
}
