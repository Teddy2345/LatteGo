<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Repositories;

/**
 * Las compras de insumos: el papel del proveedor y lo que cada linea mete
 * al almacen. Trabaja con arreglos planos, igual que el de recetas, porque
 * una compra es un documento con lineas y no una entidad con reglas propias.
 */
interface CompraRepository
{
    /** @return int id de la compra registrada */
    public function registrar(array $datos, int $usuarioId): int;

    /** @return array<int, array<string, mixed>> */
    public function listar(int $limite = 100): array;

    /** @return array<string, mixed> */
    public function buscar(int $id): array;

    /** Lo gastado en insumos entre dos fechas, para el panel de almacen. */
    public function totalGastado(string $desde, string $hasta): float;
}
