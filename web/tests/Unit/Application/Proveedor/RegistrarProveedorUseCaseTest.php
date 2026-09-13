<?php

declare(strict_types=1);

use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use App\Domain\Proveedor\Exceptions\CedulaDuplicadaException;
use Tests\Support\InMemoryProveedorRepository;

function datosProveedorValido(array $override = []): RegistrarProveedorData
{
    $datos = array_merge([
        'nombre' => 'Pedro Choque',
        'cedula' => '456789',
        'telefono' => '71234567',
        'finca' => 'Finca Huata',
        'litrosProm' => 40,
        'precioLitro' => 3.5,
        'rutaId' => 1,
    ], $override);

    return new RegistrarProveedorData(...$datos);
}

test('registra un proveedor nuevo con activo=true por defecto', function () {
    $useCase = new RegistrarProveedorUseCase(new InMemoryProveedorRepository());

    $resultado = $useCase->ejecutar(datosProveedorValido());

    expect($resultado->id)->toBe(1)
        ->and($resultado->cedula)->toBe('456789')
        ->and($resultado->activo)->toBeTrue();
});

test('rechaza el registro si la cedula ya existe', function () {
    $repositorio = new InMemoryProveedorRepository();
    $useCase = new RegistrarProveedorUseCase($repositorio);

    $useCase->ejecutar(datosProveedorValido());

    $useCase->ejecutar(datosProveedorValido(['nombre' => 'Otro Nombre']));
})->throws(CedulaDuplicadaException::class);
