<?php

declare(strict_types=1);

use App\Domain\Proveedor\Exceptions\NombreInvalidoException;
use App\Domain\Proveedor\ValueObjects\Nombre;

test('acepta un nombre valido', function () {
    expect((string) new Nombre('Maria Quispe'))->toBe('Maria Quispe');
});

test('rechaza un nombre vacio', function () {
    new Nombre('   ');
})->throws(NombreInvalidoException::class);

test('rechaza un nombre de mas de 120 caracteres', function () {
    new Nombre(str_repeat('a', 121));
})->throws(NombreInvalidoException::class);
