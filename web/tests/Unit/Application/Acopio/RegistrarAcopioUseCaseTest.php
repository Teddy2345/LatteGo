<?php

declare(strict_types=1);

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Domain\Acopio\Exceptions\PerdidaInvalidaException;
use Tests\Support\InMemoryAcopioRepository;

function datosAcopioValido(array $override = []): RegistrarAcopioData
{
    $datos = array_merge([
        'proveedorId' => 1,
        'acopiadorId' => 2,
        'rutaId' => 1,
        'fecha' => '2024-01-04',
        'cantidadLitros' => 25.0,
        'observaciones' => null,
        'perdidaLitros' => null,
        'motivoPerdida' => null,
    ], $override);

    return new RegistrarAcopioData(...$datos);
}

test('registra un acopio pendiente de sincronizar', function () {
    $useCase = new RegistrarAcopioUseCase(new InMemoryAcopioRepository());

    $resultado = $useCase->ejecutar(datosAcopioValido());

    expect($resultado->id)->toBe(1)
        ->and($resultado->estado)->toBe('pendiente_sincronizar')
        ->and($resultado->cantidadLitros)->toBe(25.0);
});

test('rechaza un acopio con perdida sin motivo', function () {
    $useCase = new RegistrarAcopioUseCase(new InMemoryAcopioRepository());

    $useCase->ejecutar(datosAcopioValido(['perdidaLitros' => 2.0]));
})->throws(PerdidaInvalidaException::class);
