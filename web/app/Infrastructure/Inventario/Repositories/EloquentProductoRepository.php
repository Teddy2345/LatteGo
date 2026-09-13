<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventario\Repositories;

use App\Domain\Inventario\Entities\Producto;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Domain\Inventario\ValueObjects\TipoProducto;
use App\Infrastructure\Inventario\Models\ProductoModel;

final class EloquentProductoRepository implements ProductoRepository
{
    public function guardar(Producto $producto): Producto
    {
        $modelo = $producto->id === null
            ? new ProductoModel()
            : ProductoModel::query()->findOrFail($producto->id);

        $modelo->fill([
            'nombre' => $producto->nombre,
            'tipo' => $producto->tipo->value,
            'categoria' => $producto->categoria,
            'foto_path' => $producto->fotoPath,
            'descripcion' => $producto->descripcion,
            'unidad' => $producto->unidad,
            'precio_referencia' => $producto->precioReferencia,
            'stock_minimo' => $producto->stockMinimo,
            'activo' => $producto->activo,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Producto
    {
        $modelo = ProductoModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarActivos(): array
    {
        return ProductoModel::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (ProductoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarTodos(): array
    {
        return ProductoModel::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (ProductoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    private function aDominio(ProductoModel $modelo): Producto
    {
        return new Producto(
            id: $modelo->id,
            nombre: $modelo->nombre,
            tipo: TipoProducto::from($modelo->tipo),
            categoria: $modelo->categoria,
            unidad: $modelo->unidad,
            precioReferencia: (float) $modelo->precio_referencia,
            stockMinimo: $modelo->stock_minimo === null ? null : (float) $modelo->stock_minimo,
            activo: (bool) $modelo->activo,
            descripcion: $modelo->descripcion,
            fotoPath: $modelo->foto_path,
        );
    }
}
