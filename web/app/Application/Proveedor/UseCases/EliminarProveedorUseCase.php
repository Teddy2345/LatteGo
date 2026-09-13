<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * Elimina un proveedor. Restringido a permiso proveedores.eliminar
 * (aplicado en la capa de Presentation via Policy, no aqui).
 */
final class EliminarProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(int $id): void
    {
        if ($this->proveedores->buscarPorId($id) === null) {
            throw new ProveedorNoEncontradoException("No existe un proveedor con id {$id}.");
        }

        $this->proveedores->eliminar($id);
    }
}
