<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\ActualizarProveedorData;
use App\Application\Proveedor\DTOs\ProveedorData;
use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\ValueObjects\LitrosPromedio;
use App\Domain\Proveedor\ValueObjects\Nombre;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;

/**
 * Actualiza los datos editables de un proveedor existente. La cedula no se
 * toca aqui porque es inmutable tras el registro, y la ruta se cambia
 * exclusivamente via ReasignarRutaProveedorUseCase.
 */
final class ActualizarProveedorUseCase
{
    public function __construct(
        private readonly ProveedorRepository $proveedores,
    ) {
    }

    public function ejecutar(ActualizarProveedorData $datos): ProveedorData
    {
        $proveedor = $this->proveedores->buscarPorId($datos->id);

        if ($proveedor === null) {
            throw new ProveedorNoEncontradoException("No existe un proveedor con id {$datos->id}.");
        }

        $actualizado = $proveedor->actualizarDatos(
            nombre: new Nombre($datos->nombre),
            telefono: $datos->telefono,
            finca: $datos->finca,
            litrosProm: new LitrosPromedio($datos->litrosProm),
            precioLitro: new PrecioLitro($datos->precioLitro),
        );

        return ProveedorData::desdeEntidad($this->proveedores->guardar($actualizado));
    }
}
