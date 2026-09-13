<?php

declare(strict_types=1);

use App\Application\Acopio\UseCases\ListarAcopiosPendientesUseCase;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Acopio\UseCases\SincronizarAcopioUseCase;
use App\Domain\Acopio\Exceptions\AcopioNoEncontradoException;
use Tests\Support\InMemoryAcopioRepository;

test('sincronizar saca al acopio de la lista de pendientes', function () {
    $repositorio = new InMemoryAcopioRepository();
    $registrar = new RegistrarAcopioUseCase($repositorio);
    $sincronizar = new SincronizarAcopioUseCase($repositorio);
    $listarPendientes = new ListarAcopiosPendientesUseCase($repositorio);

    $acopio = $registrar->ejecutar(datosAcopioValido());

    expect($listarPendientes->ejecutar())->toHaveCount(1);

    $resultado = $sincronizar->ejecutar($acopio->id);

    expect($resultado->estado)->toBe('sincronizado')
        ->and($listarPendientes->ejecutar())->toHaveCount(0);
});

test('lanza excepcion si el acopio no existe', function () {
    (new SincronizarAcopioUseCase(new InMemoryAcopioRepository()))->ejecutar(999);
})->throws(AcopioNoEncontradoException::class);
