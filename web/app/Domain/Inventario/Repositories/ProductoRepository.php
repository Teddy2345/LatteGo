<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Repositories;

use App\Domain\Inventario\Entities\Producto;

interface ProductoRepository
{
    public function guardar(Producto $producto): Producto;

    public function buscarPorId(int $id): ?Producto;

    /**
     * @return Producto[]
     */
    public function listarActivos(): array;

    /**
     * @return Producto[]
     */
    public function listarTodos(): array;
}
