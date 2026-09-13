<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Inventario\Entities\Producto;
use App\Domain\Inventario\Repositories\ProductoRepository;

final class InMemoryProductoRepository implements ProductoRepository
{
    /** @var array<int, Producto> */
    private array $productos = [];

    private int $siguienteId = 1;

    public function guardar(Producto $producto): Producto
    {
        $guardado = $producto->id === null
            ? new Producto(
                id: $this->siguienteId++,
                nombre: $producto->nombre,
                tipo: $producto->tipo,
                categoria: $producto->categoria,
                unidad: $producto->unidad,
                precioReferencia: $producto->precioReferencia,
                stockMinimo: $producto->stockMinimo,
                activo: $producto->activo,
                descripcion: $producto->descripcion,
                fotoPath: $producto->fotoPath,
            )
            : $producto;

        $this->productos[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Producto
    {
        return $this->productos[$id] ?? null;
    }

    public function listarActivos(): array
    {
        return array_values(array_filter($this->productos, static fn (Producto $p): bool => $p->activo));
    }

    public function listarTodos(): array
    {
        return array_values($this->productos);
    }
}
