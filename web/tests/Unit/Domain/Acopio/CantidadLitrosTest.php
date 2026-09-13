<?php

declare(strict_types=1);

use App\Domain\Acopio\Exceptions\CantidadLitrosInvalidaException;
use App\Domain\Acopio\ValueObjects\CantidadLitros;

test('acepta una cantidad mayor a 0', function () {
    expect((new CantidadLitros(15.5))->valor)->toBe(15.5);
});

test('rechaza una cantidad igual a 0', function () {
    new CantidadLitros(0.0);
})->throws(CantidadLitrosInvalidaException::class);

test('rechaza una cantidad negativa', function () {
    new CantidadLitros(-3.0);
})->throws(CantidadLitrosInvalidaException::class);
