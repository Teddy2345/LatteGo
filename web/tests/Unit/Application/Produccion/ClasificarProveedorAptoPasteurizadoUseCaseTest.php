<?php

declare(strict_types=1);

use App\Application\Produccion\UseCases\ClasificarProveedorAptoPasteurizadoUseCase;
use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\ValueObjects\Densidad;
use App\Domain\Calidad\ValueObjects\Ph;
use App\Domain\Calidad\ValueObjects\PorcentajeAguaAgregada;
use App\Domain\Calidad\ValueObjects\PruebaAlcohol;
use Tests\Support\InMemoryCalidadRepository;

function registrarAnalisis(InMemoryCalidadRepository $repo, int $proveedorId, string $fecha, bool $aceptada): void
{
    $repo->guardar(Calidad::crear(
        proveedorId: $proveedorId,
        acopioId: 1,
        fecha: new DateTimeImmutable($fecha),
        temperatura: 4.0,
        grasa: 3.5,
        solidosNoGrasos: 8.5,
        densidad: new Densidad($aceptada ? 30.0 : 22.0),
        proteina: 3.2,
        lactosa: 4.6,
        sales: 0.7,
        aguaAgregada: new PorcentajeAguaAgregada($aceptada ? 0.0 : 3.0),
        ph: new Ph(6.7),
        pruebaAlcohol: PruebaAlcohol::Aceptada,
        motivoRechazoManual: null,
    ));
}

test('sin historial de calidad se considera apto', function () {
    $resultado = (new ClasificarProveedorAptoPasteurizadoUseCase(new InMemoryCalidadRepository()))->ejecutar(1);

    expect($resultado->apto)->toBeTrue();
});

test('apto si el analisis mas reciente fue aceptado', function () {
    $repo = new InMemoryCalidadRepository();
    registrarAnalisis($repo, 1, '2024-01-01', aceptada: false);
    registrarAnalisis($repo, 1, '2024-01-10', aceptada: true);

    $resultado = (new ClasificarProveedorAptoPasteurizadoUseCase($repo))->ejecutar(1);

    expect($resultado->apto)->toBeTrue();
});

test('no apto si el analisis mas reciente fue rechazado', function () {
    $repo = new InMemoryCalidadRepository();
    registrarAnalisis($repo, 1, '2024-01-01', aceptada: true);
    registrarAnalisis($repo, 1, '2024-01-10', aceptada: false);

    $resultado = (new ClasificarProveedorAptoPasteurizadoUseCase($repo))->ejecutar(1);

    expect($resultado->apto)->toBeFalse();
});
