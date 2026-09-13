<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * Activa o desactiva un proveedor. Un proveedor con activo=false no debe
 * aparecer en las listas de acopio activas (ver
 * ProveedorRepository::listarActivos()).
 */
final class CambiarEstadoProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(int $proveedorId, bool $activo): ProveedorData
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor === null) {
            throw new ProveedorNoEncontradoException("No existe un proveedor con id {$proveedorId}.");
        }

        $actualizado = $activo ? $proveedor->activar() : $proveedor->desactivar();

        return ProveedorData::desdeEntidad($this->proveedores->guardar($actualizado));
    }
}
