<?php

declare(strict_types=1);

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Calidad\UseCases\RegistrarCalidadUseCase;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use App\Domain\Calidad\Exceptions\AcopioDeOtroProveedorException;
use Tests\Support\InMemoryAcopioRepository;
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
    $acopioRepo = new InMemoryAcopioRepository();
    $registrarCalidad = new RegistrarCalidadUseCase(
        $calidadRepo,
        new RegistrarNotificacionUseCase($notificacionRepo),
        $proveedorRepo,
        new InMemoryRutaRepository(),
        new InMemoryTransactionManager(),
        $acopioRepo,
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

    // Es el acopio 1, el que usan por defecto los analisis de estas pruebas.
    (new RegistrarAcopioUseCase($acopioRepo, $calidadRepo, new InMemoryProveedorRepository()))->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-04',
        cantidadLitros: 40.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));

    return [$proveedorRepo, $registrarCalidad, $proveedor, $notificacionRepo, $acopioRepo, $calidadRepo];
}

test('acepta un analisis sin agua añadida y con alcohol aceptado', function () {
    [, $registrarCalidad, $proveedor] = crearContextoCalidad();

    $resultado = $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id]));

    expect($resultado->resultado)->toBe('aceptada')
        ->and($resultado->sancionAplicada)->toBe('ninguna');
});

test('la primera vez que añade agua esa entrega no se paga', function () {
    [$proveedorRepo, $registrarCalidad, $proveedor] = crearContextoCalidad();

    $resultado = $registrarCalidad->ejecutar(datosCalidadValido([
        'proveedorId' => $proveedor->id,
        'aguaAgregada' => 3.0,
    ]));

    expect($resultado->resultado)->toBe('rechazada')
        ->and($resultado->motivoRechazo)->toBe('adulteracion')
        ->and($resultado->sancionAplicada)->toBe('sin_pago_entrega')
        ->and($proveedorRepo->buscarPorId($proveedor->id)->activo)->toBeTrue();
});

test('si reincide queda suspendido la semana, pero no se le da de baja', function () {
    // La suspension vence sola al terminar la semana: si se lo desactivara,
    // quedaria fuera hasta que un administrador se acordara de reactivarlo.
    [$proveedorRepo, $registrarCalidad, $proveedor, , $acopioRepo, $calidadRepo] = crearContextoCalidad();

    $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'aguaAgregada' => 3.0]));

    // Reincidir es volver a añadir agua en otra entrega (el acopio 2).
    (new RegistrarAcopioUseCase($acopioRepo, $calidadRepo, new InMemoryProveedorRepository()))->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-05',
        cantidadLitros: 38.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $resultado = $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'acopioId' => 2, 'aguaAgregada' => 4.0]));

    expect($resultado->sancionAplicada)->toBe('suspension_semana')
        ->and($proveedorRepo->buscarPorId($proveedor->id)->activo)->toBeTrue();
});

test('la misma entrega no se analiza dos veces ni cuenta como reincidencia', function () {
    // Un doble clic o un reanalisis de la misma entrega no debe suspender
    // al proveedor la semana entera por una sola vez que añadio agua.
    [, $registrarCalidad, $proveedor, , , $calidadRepo] = crearContextoCalidad();

    $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'aguaAgregada' => 3.0]));

    expect(fn () => $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id, 'aguaAgregada' => 3.0])))
        ->toThrow(\App\Domain\Calidad\Exceptions\AcopioYaAnalizadoException::class, 'Ese acopio ya tiene su análisis de calidad registrado.');

    expect($calidadRepo->listarTodos())->toHaveCount(1);
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
        ->and($notificacion->datos['Sanción'])->toBe('Entrega sin pago');
});

test('un analisis aceptado no genera ninguna notificacion', function () {
    [, $registrarCalidad, $proveedor, $notificacionRepo] = crearContextoCalidad();

    $registrarCalidad->ejecutar(datosCalidadValido(['proveedorId' => $proveedor->id]));

    expect($notificacionRepo->listarRecientes(10))->toHaveCount(0);
});

test('no acepta un analisis cuyo acopio es de otro proveedor', function () {
    // Cargaria la sancion por agua a un ganadero y le quitaria el pago de la
    // entrega a otro que no hizo nada.
    [$proveedorRepo, $registrarCalidad] = crearContextoCalidad();

    $otro = (new RegistrarProveedorUseCase($proveedorRepo))->ejecutar(new RegistrarProveedorData(
        nombre: 'Otro Proveedor',
        cedula: '998877',
        telefono: null,
        finca: null,
        litrosProm: 30,
        precioLitro: 3.5,
        rutaId: null,
    ));

    // El acopio 1 es del "Proveedor Test", no de este otro.
    expect(fn () => $registrarCalidad->ejecutar(datosCalidadValido([
        'proveedorId' => $otro->id,
        'aguaAgregada' => 5.0,
    ])))->toThrow(AcopioDeOtroProveedorException::class);
});

test('no acepta un analisis de un acopio que no existe', function () {
    [, $registrarCalidad, $proveedor] = crearContextoCalidad();

    expect(fn () => $registrarCalidad->ejecutar(datosCalidadValido([
        'proveedorId' => $proveedor->id,
        'acopioId' => 999,
    ])))->toThrow(AcopioDeOtroProveedorException::class);
});
