<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Repositories;

use App\Domain\Proveedor\Entities\Proveedor;

interface ProveedorRepository
{
    public function guardar(Proveedor $proveedor): Proveedor;

    public function buscarPorId(int $id): ?Proveedor;

    public function buscarPorCedula(string $cedula): ?Proveedor;

    public function existeCedula(string $cedula, ?int $ignorarId = null): bool;

    /**
     * @return Proveedor[]
     */
    public function listarActivos(): array;

    /**
     * @return Proveedor[]
     */
    public function listarTodos(): array;

    public function eliminar(int $id): void;
}
