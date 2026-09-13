<?php

declare(strict_types=1);

use App\Domain\Produccion\ValueObjects\LitrosProcesados;
use App\Domain\Produccion\ValueObjects\QuesosProducidos;
use App\Domain\Produccion\ValueObjects\RendimientoPorcentaje;

test('calcula quesos por cada 100 litros', function () {
    $rendimiento = RendimientoPorcentaje::calcular(
        new LitrosProcesados(1000.0),
        new QuesosProducidos(115),
    );

    expect($rendimiento->valor)->toBe(11.5);
});

test('esta dentro del rango esperado entre 11 y 12', function () {
    $rendimiento = RendimientoPorcentaje::calcular(new LitrosProcesados(100.0), new QuesosProducidos(11));

    expect($rendimiento->dentroDelRangoEsperado())->toBeTrue();
});

test('fuera del rango esperado no bloquea, solo se marca', function () {
    $rendimiento = RendimientoPorcentaje::calcular(new LitrosProcesados(100.0), new QuesosProducidos(8));

    expect($rendimiento->dentroDelRangoEsperado())->toBeFalse();
});
