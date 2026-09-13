<?php

declare(strict_types=1);

use App\Application\Produccion\DTOs\RegistrarDespachoData;
use App\Application\Produccion\DTOs\RegistrarProduccionData;
use App\Application\Produccion\UseCases\RegistrarDespachoUseCase;
use App\Application\Produccion\UseCases\RegistrarProduccionUseCase;
use App\Domain\Produccion\Exceptions\ProduccionNoEncontradaException;
use Tests\Support\InMemoryDespachoRepository;
use Tests\Support\InMemoryMovimientoInventarioRepository;
use Tests\Support\InMemoryProduccionRepository;
use Tests\Support\InMemoryProductoRepository;
use Tests\Support\InMemoryTransactionManager;

function registrarProduccionUseCaseDePrueba(InMemoryProduccionRepository $produccionRepo): RegistrarProduccionUseCase
{
    return new RegistrarProduccionUseCase(
        $produccionRepo,
        new InMemoryMovimientoInventarioRepository(),
        new InMemoryProductoRepository(),
        new InMemoryTransactionManager(),
    );
}

test('calcula la merma a partir de la produccion referenciada', function () {
    $produccionRepo = new InMemoryProduccionRepository();
    $despachoRepo = new InMemoryDespachoRepository();

    $produccion = registrarProduccionUseCaseDePrueba($produccionRepo)->ejecutar(new RegistrarProduccionData(
        fecha: '2024-01-04',
        litrosProcesados: 1000.0,
        quesosProducidos: 115,
        jefaProduccionId: null,
        observaciones: null,
    ));

    $despacho = (new RegistrarDespachoUseCase($despachoRepo, $produccionRepo))->ejecutar(new RegistrarDespachoData(
        produccionId: $produccion->id,
        despachadorId: 5,
        quesosRecibidos: 110,
        quesosDespachados: 108,
        observaciones: null,
    ));

    expect($despacho->merma)->toBe(5);
});

test('lanza excepcion si la produccion no existe', function () {
    $despachoRepo = new InMemoryDespachoRepository();
    $produccionRepo = new InMemoryProduccionRepository();

    (new RegistrarDespachoUseCase($despachoRepo, $produccionRepo))->ejecutar(new RegistrarDespachoData(
        produccionId: 999,
        despachadorId: null,
        quesosRecibidos: 10,
        quesosDespachados: 10,
        observaciones: null,
    ));
})->throws(ProduccionNoEncontradaException::class);
