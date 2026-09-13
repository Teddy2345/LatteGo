<?php

declare(strict_types=1);

use App\Domain\Produccion\Entities\Despacho;
use App\Domain\Produccion\Exceptions\CantidadDespachoInvalidaException;

test('sin diferencia entre producido y recibido no hay merma', function () {
    $despacho = Despacho::crear(
        produccionId: 1,
        despachadorId: 2,
        quesosProducidosReferencia: 100,
        quesosRecibidos: 100,
        quesosDespachados: 100,
        observaciones: null,
    );

    expect($despacho->merma)->toBe(0);
});

test('si lo recibido difiere de lo producido se registra la merma', function () {
    $despacho = Despacho::crear(
        produccionId: 1,
        despachadorId: 2,
        quesosProducidosReferencia: 120,
        quesosRecibidos: 110,
        quesosDespachados: 108,
        observaciones: null,
    );

    expect($despacho->merma)->toBe(10);
});

test('la merma nunca es negativa aunque se reciba mas de lo producido', function () {
    $despacho = Despacho::crear(
        produccionId: 1,
        despachadorId: null,
        quesosProducidosReferencia: 100,
        quesosRecibidos: 105,
        quesosDespachados: 105,
        observaciones: null,
    );

    expect($despacho->merma)->toBe(0);
});

test('rechaza cantidades negativas', function () {
    Despacho::crear(
        produccionId: 1,
        despachadorId: null,
        quesosProducidosReferencia: 100,
        quesosRecibidos: -5,
        quesosDespachados: 0,
        observaciones: null,
    );
})->throws(CantidadDespachoInvalidaException::class);
