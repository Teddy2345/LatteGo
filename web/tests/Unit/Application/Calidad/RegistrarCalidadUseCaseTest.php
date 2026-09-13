<?php

declare(strict_types=1);

use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Calidad\UseCases\RegistrarCalidadUseCase;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\CambiarEstadoProveedorUseCase;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use Tests\Support\InMemoryCalidadRepository;
use Tests\Support\InMemoryNotificacionRepository;
use Tests\Support\InMemoryProveedorRepository;
use Tests\Support\InMemoryRutaRepository;
use Tests\Support\InMemoryTransactionManager;

function datosCalidadValido(array $overrides = []): RegistrarCalidadData
{
    $datos = array_merge([
        'proveedorId' => 1,
        'acopioId' => 1,
        'fecha' => '2024-01-04',
        'temperatura' => 4.0,
        'grasa' => 3.5,
        'solidosNoGrasos' => 8.5,
        'densidad' => 30.5,
        'proteina' => 3.2,
        'lactosa' => 4.6,
        'sales' => 0.7,
        'aguaAgregada' => 0.0,
        'ph' => 6.7,
        'pruebaAlcoholAceptada' => true,
        'motivoRechazoManual' => null,
    ], $overrides);

    return new RegistrarCalidadData(...$datos);
}

function crearContextoCalidad(): array
{
    $proveedorRepo = new InMemoryProveedorRepository();
    $calidadRepo = new InMemoryCalidadRepository();
    $notificacionRepo = new InMemoryNotificacionRepository();
    $cambiarEstado = new CambiarEstadoProveedorUseCase($proveedorRepo);
    $registrarCalidad = new RegistrarCalidadUseCase(
        $calidadRepo,
        $cambiarEstado,
        new RegistrarNotificacionUseCase($notificacionRepo),
        $proveedorRepo,
        new InMemoryRutaRepository(),
        new InMemoryTransactionManager(),
    );

    $proveedor = (new RegistrarProveedorUseCase($proveedorRepo))->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Test',
        cedula: '445566',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 3.5,
        rutaId: null,
    ));

    return [$proveedorRepo, $registrarCalidad, $proveedor, $notificacionRepo];
}

test('acepta un analisis sin agua añadida y con alcohol aceptado', function () {
    [, $registrarCalidad, $proveedor] = crearContextoCalidad();

    $resultado = $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id]));

    expect($resultado->resultado)->toBe('aceptada')
        ->and($resultado->sancionAplicada)->toBe('ninguna');
});

test('primera adulteracion descuenta la semana y no desactiva al proveedor', function () {
    [$proveedorRepo, $registrarCalidad, $proveedor] = crearContextoCalidad();

    $resultado = $registrarCalidad->ejecutar(datosCalidadValido([
        'proveedorId' => $proveedor->id,
        'aguaAgregada' => 3.0,
    ]));

    expect($resultado->resultado)->toBe('rechazada')
        ->and($resultado->motivoRechazo)->toBe('adulteracion')
        ->and($resultado->sancionAplicada)->toBe('descuento_semana')
        ->and($proveedorRepo->buscarPorId($proveedor->id)->activo)->toBeTrue();
});

test('segunda adulteracion retira temporalmente y desactiva al proveedor', function () {
    [$proveedorRepo, $registrarCalidad, $proveedor] = crearContextoCalidad();

    $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'aguaAgregada' => 3.0]));
    $resultado = $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'aguaAgregada' => 4.0]));

    expect($resultado->sancionAplicada)->toBe('retiro_temporal')
        ->and($proveedorRepo->buscarPorId($proveedor->id)->activo)->toBeFalse();
});

test('un rechazo genera una notificacion de alerta con los datos del proveedor', function () {
    [, $registrarCalidad, $proveedor, $notificacionRepo] = crearContextoCalidad();

    $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'aguaAgregada' => 5.2]));

    $notificaciones = $notificacionRepo->listarRecientes(10);
    expect($notificaciones)->toHaveCount(1);

    $notificacion = $notificaciones[0];
    expect($notificacion->tipo)->toBe('calidad_rechazo')
        ->and($notificacion->nivel->value)->toBe('alerta')
        ->and($notificacion->datos['Proveedor'])->toBe('Proveedor Test')
        ->and($notificacion->datos['Motivo'])->toBe('Adulteración')
        ->and($notificacion->datos['Agua añadida'])->toBe('5.20 %')
        ->and($notificacion->datos['Sanción'])->toBe('Descuento de la semana');
});

test('un analisis aceptado no genera ninguna notificacion', function () {
    [, $registrarCalidad, $proveedor, $notificacionRepo] = crearContextoCalidad();

    $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id]));

    expect($notificacionRepo->listarRecientes(10))->toHaveCount(0);
});
