<?php

declare(strict_types=1);

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Acopio\UseCases\SincronizarAcopioUseCase;
use App\Application\Pagos\UseCases\GenerarPlanillaPagoUseCase;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use Tests\Support\InMemoryAcopioRepository;
use Tests\Support\InMemoryPagoRepository;
use Tests\Support\InMemoryProveedorRepository;

function crearContextoPlanilla(): array
{
    $proveedorRepo = new InMemoryProveedorRepository();
    $acopioRepo = new InMemoryAcopioRepository();
    $pagoRepo = new InMemoryPagoRepository();

    $registrarProveedor = new RegistrarProveedorUseCase($proveedorRepo);
    $registrarAcopio = new RegistrarAcopioUseCase($acopioRepo);
    $sincronizarAcopio = new SincronizarAcopioUseCase($acopioRepo);
    $generarPlanilla = new GenerarPlanillaPagoUseCase($pagoRepo, $acopioRepo, $proveedorRepo);

    return [$proveedorRepo, $acopioRepo, $pagoRepo, $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla];
}

test('genera un pago por el total de litros sincronizados en la semana', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = $registrarProveedor->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Semana',
        cedula: '778899',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 3.5,
        rutaId: null,
    ));

    $acopio1 = $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-04',
        cantidadLitros: 100.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $acopio2 = $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-09',
        cantidadLitros: 50.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $sincronizarAcopio->ejecutar($acopio1->id);
    $sincronizarAcopio->ejecutar($acopio2->id);

    $generados = $generarPlanilla->ejecutar('2024-01-04');

    expect($generados)->toHaveCount(1)
        ->and($generados[0]->totalLitros)->toBe(150.0)
        ->and($generados[0]->totalPagar)->toBe(525.0)
        ->and($generados[0]->estado)->toBe('pendiente');
});

test('ignora acopios no sincronizados', function () {
    [, , , $registrarProveedor, $registrarAcopio, , $generarPlanilla] = crearContextoPlanilla();

    $proveedor = $registrarProveedor->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Pendiente',
        cedula: '112200',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 3.0,
        rutaId: null,
    ));

    $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-04',
        cantidadLitros: 100.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));

    $generados = $generarPlanilla->ejecutar('2024-01-04');

    expect($generados)->toHaveCount(0);
});

test('no duplica un pago ya generado para la misma semana', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = $registrarProveedor->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Duplicado',
        cedula: '334455',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 3.0,
        rutaId: null,
    ));

    $acopio = $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-04',
        cantidadLitros: 80.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $sincronizarAcopio->ejecutar($acopio->id);

    $primeraVez = $generarPlanilla->ejecutar('2024-01-04');
    $segundaVez = $generarPlanilla->ejecutar('2024-01-04');

    expect($primeraVez)->toHaveCount(1)
        ->and($segundaVez)->toHaveCount(0);
});
