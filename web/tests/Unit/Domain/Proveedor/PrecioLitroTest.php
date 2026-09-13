<?php

declare(strict_types=1);

use App\Domain\Proveedor\Exceptions\PrecioLitroInvalidoException;
use App\Domain\Proveedor\ValueObjects\PrecioLitro;

test('acepta un precio mayor a 0', function () {
    $precio = new PrecioLitro(3.5);

    expect($precio->valor)->toBe(3.5);
});

test('rechaza un precio igual a 0', function () {
    new PrecioLitro(0.0);
})->throws(PrecioLitroInvalidoException::class);

test('rechaza un precio negativo', function () {
    new PrecioLitro(-1.0);
})->throws(PrecioLitroInvalidoException::class);

test('redondea a 2 decimales', function () {
    $precio = new PrecioLitro(3.456);

    expect($precio->valor)->toBe(3.46);
});
