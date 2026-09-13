<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * Reasigna un proveedor a otra ruta de acopio. El proveedor conserva su
 * historial de acopios: la reasignacion solo cambia ruta_id, los registros
 * de Acopio ya existentes no se ven afectados.
 */
final class ReasignarRutaProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(int $proveedorId, ?int $rutaId): ProveedorData
    {
        $proveedor = $this->proveedores->buscarPorId($proveedorId);

        if ($proveedor === null) {
            throw new ProveedorNoEncontradoException("No existe un proveedor con id {$proveedorId}.");
        }

        return ProveedorData::desdeEntidad(
            $this->proveedores->guardar($proveedor->reasignarRuta($rutaId)),
        );
    }
}
