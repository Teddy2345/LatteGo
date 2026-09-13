<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Repositories;

use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\ValueObjects\TipoMovimiento;

interface MovimientoInventarioRepository
{
    public function guardar(MovimientoInventario $movimiento): MovimientoInventario;

    /**
     * Recupera el movimiento creado con esa clave de idempotencia, si existe.
     */
    public function buscarPorRequestId(string $requestId): ?MovimientoInventario;

    /**
     * @return MovimientoInventario[]
     */
    public function listarPorTipo(TipoMovimiento $tipo): array;

    /**
     * Stock disponible de cada producto, indexado por id de producto.
     *
     * @return array<int, float>
     */
    public function stockPorProducto(): array;

    public function stockDe(int $productoId): float;

    /**
     * @return MovimientoInventario[] Movimientos (entrada de producto
     * terminado y salidas de insumo) ligados a esa produccion.
     */
    public function listarPorProduccion(int $produccionId): array;

    /**
     * Costo promedio ponderado de las compras de ese insumo que registraron
     * un precio unitario. Null si nunca se registro un costo (compras
     * historicas sin ese dato, o el producto nunca se compro).
     */
    public function costoPromedioPonderado(int $productoId): ?float;
}
