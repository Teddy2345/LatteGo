<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * Obtiene el detalle de un proveedor por id.
 */
final class ObtenerProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(int $id): ProveedorData
    {
        $proveedor = $this->proveedores->buscarPorId($id);

        if ($proveedor === null) {
            throw new ProveedorNoEncontradoException("No existe un proveedor con id {$id}.");
        }

        return ProveedorData::desdeEntidad($proveedor);
    }
}
