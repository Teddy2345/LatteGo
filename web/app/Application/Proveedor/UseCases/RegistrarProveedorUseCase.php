<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\ProveedorData;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Exceptions\CedulaDuplicadaException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\ValueObjects\Cedula;
use App\Domain\Proveedor\ValueObjects\LitrosPromedio;
use App\Domain\Proveedor\ValueObjects\Nombre;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;

/**
 * Registra un nuevo proveedor. Aplica las reglas de negocio: la cedula debe
 * ser unica en el sistema, tener al menos 6 caracteres, y el precio por
 * litro debe ser mayor a 0 (estas dos ultimas via los Value Objects).
 */
final class RegistrarProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(RegistrarProveedorData $datos): ProveedorData
    {
        $cedula = new Cedula($datos->cedula);

        if ($this->proveedores->existeCedula((string) $cedula)) {
            throw new CedulaDuplicadaException("Ya existe un proveedor registrado con la cedula {$cedula}.");
        }

        $proveedor = Proveedor::crear(
            nombre: new Nombre($datos->nombre),
            cedula: $cedula,
            telefono: $datos->telefono,
            finca: $datos->finca,
            litrosProm: new LitrosPromedio($datos->litrosProm),
            precioLitro: new PrecioLitro($datos->precioLitro),
            rutaId: $datos->rutaId,
        );

        return ProveedorData::desdeEntidad($this->proveedores->guardar($proveedor));
    }
}
