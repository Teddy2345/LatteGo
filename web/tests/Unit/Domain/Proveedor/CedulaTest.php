<?php

declare(strict_types=1);

use App\Domain\Proveedor\Exceptions\CedulaInvalidaException;
use App\Domain\Proveedor\ValueObjects\Cedula;

test('acepta una cedula con 6 o mas caracteres', function () {
    $cedula = new Cedula('123456');

    expect((string) $cedula)->toBe('123456');
});

test('rechaza una cedula con menos de 6 caracteres', function () {
    new Cedula('12345');
})->throws(CedulaInvalidaException::class);

test('recorta espacios en blanco', function () {
    $cedula = new Cedula('  123456  ');

    expect((string) $cedula)->toBe('123456');
});
