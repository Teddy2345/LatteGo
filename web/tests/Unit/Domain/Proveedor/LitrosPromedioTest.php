<?php

declare(strict_types=1);

use App\Domain\Proveedor\Exceptions\LitrosPromedioInvalidoException;
use App\Domain\Proveedor\ValueObjects\LitrosPromedio;

test('acepta 0 litros', function () {
    expect((new LitrosPromedio(0))->valor)->toBe(0);
});

test('acepta un valor positivo', function () {
    expect((new LitrosPromedio(150))->valor)->toBe(150);
});

test('rechaza un valor negativo', function () {
    new LitrosPromedio(-1);
})->throws(LitrosPromedioInvalidoException::class);
