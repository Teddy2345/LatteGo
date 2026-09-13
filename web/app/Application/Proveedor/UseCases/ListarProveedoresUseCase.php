<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Repositories\ProveedorRepository;

/**
 * Lista todos los proveedores (activos e inactivos), para las pantallas
 * administrativas de gestion.
 */
final class ListarProveedoresUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    /**
     * @return ProveedorData[]
     */
    public function ejecutar(): array
    {
        return array_map(
            static fn (Proveedor $proveedor): ProveedorData => ProveedorData::desdeEntidad($proveedor),
            $this->proveedores->listarTodos(),
        );
    }
}
