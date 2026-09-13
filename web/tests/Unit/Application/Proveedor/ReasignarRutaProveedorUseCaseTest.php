<?php

declare(strict_types=1);

use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\ReasignarRutaProveedorUseCase;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use Tests\Support\InMemoryProveedorRepository;

test('reasigna la ruta de un proveedor existente', function () {
    $repositorio = new InMemoryProveedorRepository();
    $proveedor = (new RegistrarProveedorUseCase($repositorio))->ejecutar(new RegistrarProveedorData(
        nombre: 'Felix Condori',
        cedula: '998877',
        telefono: null,
        finca: null,
        litrosProm: 25,
        precioLitro: 3.0,
        rutaId: 1,
    ));

    $reasignado = (new ReasignarRutaProveedorUseCase($repositorio))->ejecutar($proveedor->id, 2);

    expect($reasignado->rutaId)->toBe(2)
        ->and($reasignado->cedula)->toBe('998877');
});

test('lanza excepcion si el proveedor no existe', function () {
    (new ReasignarRutaProveedorUseCase(new InMemoryProveedorRepository()))->ejecutar(999, 2);
})->throws(ProveedorNoEncontradoException::class);
