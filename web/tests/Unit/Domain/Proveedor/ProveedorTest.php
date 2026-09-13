<?php

declare(strict_types=1);

use App\Domain\Proveedor\Entities\Proveedor;
use App\Domain\Proveedor\Exceptions\TelefonoInvalidoException;
use App\Domain\Proveedor\ValueObjects\Cedula;
use App\Domain\Proveedor\ValueObjects\LitrosPromedio;
use App\Domain\Proveedor\ValueObjects\Nombre;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;

function proveedorValido(?int $rutaId = 1): Proveedor
{
    return Proveedor::crear(
        nombre: new Nombre('Juana Mamani'),
        cedula: new Cedula('789012'),
        telefono: '71234567',
        finca: 'Finca Huata',
        litrosProm: new LitrosPromedio(50),
        precioLitro: new PrecioLitro(3.8),
        rutaId: $rutaId,
    );
}

test('un proveedor recien creado esta activo', function () {
    expect(proveedorValido()->activo)->toBeTrue();
});

test('desactivar cambia el estado sin tocar el resto de los datos', function () {
    $proveedor = proveedorValido();

    $inactivo = $proveedor->desactivar();

    expect($inactivo->activo)->toBeFalse()
        ->and($inactivo->cedula->equals($proveedor->cedula))->toBeTrue()
        ->and($inactivo->rutaId)->toBe($proveedor->rutaId);
});

test('activar revierte la desactivacion', function () {
    $proveedor = proveedorValido()->desactivar();

    expect($proveedor->activar()->activo)->toBeTrue();
});

test('reasignar ruta solo cambia ruta_id', function () {
    $proveedor = proveedorValido(rutaId: 1);

    $reasignado = $proveedor->reasignarRuta(2);

    expect($reasignado->rutaId)->toBe(2)
        ->and($reasignado->cedula->equals($proveedor->cedula))->toBeTrue()
        ->and($reasignado->nombre->valor)->toBe($proveedor->nombre->valor)
        ->and($reasignado->activo)->toBe($proveedor->activo);
});

test('actualizar datos conserva la cedula original', function () {
    $proveedor = proveedorValido();

    $actualizado = $proveedor->actualizarDatos(
        nombre: new Nombre('Juana Mamani Choque'),
        telefono: '76543210',
        finca: 'Finca Huata Alta',
        litrosProm: new LitrosPromedio(60),
        precioLitro: new PrecioLitro(4.0),
    );

    expect($actualizado->cedula->equals($proveedor->cedula))->toBeTrue()
        ->and($actualizado->nombre->valor)->toBe('Juana Mamani Choque')
        ->and($actualizado->litrosProm->valor)->toBe(60);
});

test('rechaza un telefono de mas de 20 caracteres', function () {
    Proveedor::crear(
        nombre: new Nombre('Juana Mamani'),
        cedula: new Cedula('789012'),
        telefono: str_repeat('9', 21),
        finca: null,
        litrosProm: new LitrosPromedio(50),
        precioLitro: new PrecioLitro(3.8),
        rutaId: null,
    );
})->throws(TelefonoInvalidoException::class);
