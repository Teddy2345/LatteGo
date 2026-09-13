<?php

declare(strict_types=1);

use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\ValueObjects\Densidad;
use App\Domain\Calidad\ValueObjects\MotivoRechazo;
use App\Domain\Calidad\ValueObjects\Ph;
use App\Domain\Calidad\ValueObjects\PorcentajeAguaAgregada;
use App\Domain\Calidad\ValueObjects\PruebaAlcohol;
use App\Domain\Calidad\ValueObjects\ResultadoCalidad;
use App\Domain\Calidad\ValueObjects\SancionAplicada;

function analisisValido(array $overrides = []): Calidad
{
    $datos = array_merge([
        'proveedorId' => 1,
        'acopioId' => 1,
        'fecha' => new DateTimeImmutable('2024-01-04'),
        'temperatura' => 4.0,
        'grasa' => 3.5,
        'solidosNoGrasos' => 8.5,
        'densidad' => new Densidad(30.5),
        'proteina' => 3.2,
        'lactosa' => 4.6,
        'sales' => 0.7,
        'aguaAgregada' => new PorcentajeAguaAgregada(0.0),
        'ph' => new Ph(6.7),
        'pruebaAlcohol' => PruebaAlcohol::Aceptada,
        'motivoRechazoManual' => null,
    ], $overrides);

    return Calidad::crear(...$datos);
}

test('se acepta cuando no hay agua añadida y el alcohol pasa', function () {
    $calidad = analisisValido();

    expect($calidad->resultado)->toBe(ResultadoCalidad::Aceptada)
        ->and($calidad->motivoRechazo)->toBeNull();
});

test('se rechaza automaticamente por adulteracion si hay agua añadida', function () {
    $calidad = analisisValido(['aguaAgregada' => new PorcentajeAguaAgregada(2.5)]);

    expect($calidad->resultado)->toBe(ResultadoCalidad::Rechazada)
        ->and($calidad->motivoRechazo)->toBe(MotivoRechazo::Adulteracion);
});

test('se rechaza automaticamente por adulteracion si falla la prueba de alcohol', function () {
    $calidad = analisisValido(['pruebaAlcohol' => PruebaAlcohol::Rechazada]);

    expect($calidad->resultado)->toBe(ResultadoCalidad::Rechazada)
        ->and($calidad->motivoRechazo)->toBe(MotivoRechazo::Adulteracion);
});

test('permite rechazo manual por otro motivo cuando no hay adulteracion', function () {
    $calidad = analisisValido(['motivoRechazoManual' => MotivoRechazo::Acidez]);

    expect($calidad->resultado)->toBe(ResultadoCalidad::Rechazada)
        ->and($calidad->motivoRechazo)->toBe(MotivoRechazo::Acidez);
});

test('la adulteracion automatica no se puede sobreescribir con otro motivo', function () {
    $calidad = analisisValido([
        'aguaAgregada' => new PorcentajeAguaAgregada(1.0),
        'motivoRechazoManual' => MotivoRechazo::Acidez,
    ]);

    expect($calidad->motivoRechazo)->toBe(MotivoRechazo::Adulteracion);
});

test('sin adulteracion no aplica sancion', function () {
    $calidad = analisisValido(['motivoRechazoManual' => MotivoRechazo::Acidez]);

    expect($calidad->determinarSancion(0))->toBe(SancionAplicada::Ninguna);
});

test('primera adulteracion descuenta la semana', function () {
    $calidad = analisisValido(['aguaAgregada' => new PorcentajeAguaAgregada(1.0)]);

    expect($calidad->determinarSancion(0))->toBe(SancionAplicada::DescuentoSemana);
});

test('segunda adulteracion en adelante retira temporalmente', function () {
    $calidad = analisisValido(['aguaAgregada' => new PorcentajeAguaAgregada(1.0)]);

    expect($calidad->determinarSancion(1))->toBe(SancionAplicada::RetiroTemporal)
        ->and($calidad->determinarSancion(5))->toBe(SancionAplicada::RetiroTemporal);
});
