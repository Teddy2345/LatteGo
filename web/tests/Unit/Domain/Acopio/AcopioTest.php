<?php

declare(strict_types=1);

use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Exceptions\PerdidaInvalidaException;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;

function acopioValido(array $overrides = []): Acopio
{
    $datos = array_merge([
        'proveedorId' => 1,
        'acopiadorId' => 2,
        'rutaId' => 1,
        'fecha' => new DateTimeImmutable('2024-01-04'),
        'cantidadLitros' => new CantidadLitros(20.0),
        'observaciones' => null,
        'perdidaLitros' => null,
        'motivoPerdida' => null,
    ], $overrides);

    return Acopio::crear(...$datos);
}

test('un acopio recien creado queda pendiente de sincronizar', function () {
    expect(acopioValido()->estado)->toBe(EstadoAcopio::PendienteSincronizar);
});

test('marcarSincronizado cambia el estado sin tocar el resto', function () {
    $acopio = acopioValido();

    $sincronizado = $acopio->marcarSincronizado();

    expect($sincronizado->estado)->toBe(EstadoAcopio::Sincronizado)
        ->and($sincronizado->cantidadLitros->valor)->toBe($acopio->cantidadLitros->valor)
        ->and($sincronizado->proveedorId)->toBe($acopio->proveedorId);
});

test('rechaza perdida positiva sin motivo', function () {
    acopioValido(['perdidaLitros' => 5.0, 'motivoPerdida' => null]);
})->throws(PerdidaInvalidaException::class);

test('rechaza perdida negativa', function () {
    acopioValido(['perdidaLitros' => -1.0, 'motivoPerdida' => 'derrame']);
})->throws(PerdidaInvalidaException::class);

test('acepta perdida con motivo', function () {
    $acopio = acopioValido(['perdidaLitros' => 3.5, 'motivoPerdida' => 'Derrame en transporte']);

    expect($acopio->perdidaLitros)->toBe(3.5)
        ->and($acopio->motivoPerdida)->toBe('Derrame en transporte');
});

test('calcula la semana de pago a partir de la fecha', function () {
    $acopio = acopioValido(['fecha' => new DateTimeImmutable('2024-01-07')]);

    $semana = $acopio->semanaPago();

    expect($semana->inicio->format('Y-m-d'))->toBe('2024-01-04')
        ->and($semana->fin->format('Y-m-d'))->toBe('2024-01-10');
});
