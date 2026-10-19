<?php

declare(strict_types=1);

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Acopio\UseCases\SincronizarAcopioUseCase;
use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Calidad\UseCases\RegistrarCalidadUseCase;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Application\Pagos\UseCases\GenerarPlanillaPagoUseCase;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use Tests\Support\InMemoryAcopioRepository;
use Tests\Support\InMemoryCalidadRepository;
use Tests\Support\InMemoryNotificacionRepository;
use Tests\Support\InMemoryPagoRepository;
use Tests\Support\InMemoryProveedorRepository;
use Tests\Support\InMemoryRutaRepository;
use Tests\Support\InMemoryTransactionManager;

function crearContextoPlanilla(): array
{
    $proveedorRepo = new InMemoryProveedorRepository();
    $acopioRepo = new InMemoryAcopioRepository();
    $pagoRepo = new InMemoryPagoRepository();
    $calidadRepo = new InMemoryCalidadRepository();

    $registrarProveedor = new RegistrarProveedorUseCase($proveedorRepo);
    $registrarAcopio = new RegistrarAcopioUseCase($acopioRepo, $calidadRepo, new InMemoryProveedorRepository());
    $sincronizarAcopio = new SincronizarAcopioUseCase($acopioRepo);
    $generarPlanilla = new GenerarPlanillaPagoUseCase($pagoRepo, $acopioRepo, $proveedorRepo, $calidadRepo);
    $registrarCalidad = new RegistrarCalidadUseCase(
        $calidadRepo,
        new RegistrarNotificacionUseCase(new InMemoryNotificacionRepository()),
        $proveedorRepo,
        new InMemoryRutaRepository(),
        new InMemoryTransactionManager(),
        $acopioRepo,
    );

    return [$proveedorRepo, $acopioRepo, $pagoRepo, $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla, $registrarCalidad];
}

function proveedorDePlanilla(RegistrarProveedorUseCase $registrar, string $cedula, float $precio = 1.70): object
{
    return $registrar->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor '.$cedula,
        cedula: $cedula,
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: $precio,
        rutaId: null,
    ));
}

function acopioSincronizado(RegistrarAcopioUseCase $registrar, SincronizarAcopioUseCase $sincronizar, int $proveedorId, string $fecha, float $litros): object
{
    $acopio = $registrar->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedorId,
        acopiadorId: null,
        rutaId: null,
        fecha: $fecha,
        cantidadLitros: $litros,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $sincronizar->ejecutar($acopio->id);

    return $acopio;
}

/** Analisis de una entrega; con agua > 0 la leche sale adulterada. */
function analizar(RegistrarCalidadUseCase $registrar, int $proveedorId, int $acopioId, string $fecha, float $agua): object
{
    return $registrar->ejecutar(new RegistrarCalidadData(
        proveedorId: $proveedorId,
        acopioId: $acopioId,
        fecha: $fecha,
        temperatura: 4.0,
        grasa: 3.5,
        solidosNoGrasos: 8.5,
        densidad: 30.5,
        proteina: 3.2,
        lactosa: 4.6,
        sales: 0.7,
        aguaAgregada: $agua,
        ph: 6.7,
        pruebaAlcoholAceptada: true,
        motivoRechazoManual: null,
    ));
}

test('genera un pago por el total de litros sincronizados en la semana', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '778899', 3.5);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 100.0);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-09', 50.0);

    $generados = $generarPlanilla->ejecutar('2024-01-04');

    expect($generados)->toHaveCount(1)
        ->and($generados[0]->totalLitros)->toBe(150.0)
        ->and($generados[0]->totalPagar)->toBe(525.0)
        ->and($generados[0]->estado)->toBe('pendiente');
});

test('ignora acopios no sincronizados', function () {
    [, , , $registrarProveedor, $registrarAcopio, , $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '112200', 3.0);

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

    expect($generarPlanilla->ejecutar('2024-01-04'))->toHaveCount(0);
});

test('no duplica un pago ya generado para la misma semana', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '334455', 3.0);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 80.0);

    expect($generarPlanilla->ejecutar('2024-01-04'))->toHaveCount(1)
        ->and($generarPlanilla->ejecutar('2024-01-04'))->toHaveCount(0);
});

test('la primera vez que añade agua no se le paga esa entrega, pero si las demas', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla, $registrarCalidad] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '445566');
    $conAgua = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 40.0);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-06', 60.0);

    $analisis = analizar($registrarCalidad, $proveedor->id, $conAgua->id, '2024-01-04', 5.0);

    $generados = $generarPlanilla->ejecutar('2024-01-04');

    // Solo se pagan los 60 L limpios, no los 40 L con agua.
    expect($analisis->sancionAplicada)->toBe('sin_pago_entrega')
        ->and($generados)->toHaveCount(1)
        ->and($generados[0]->totalLitros)->toBe(60.0)
        ->and($generados[0]->totalPagar)->toBe(102.0);
});

test('si reincide queda suspendido y no cobra nada de esa semana', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla, $registrarCalidad] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '556677');

    // Primera vez, en una semana anterior.
    $antes = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2023-12-28', 30.0);
    analizar($registrarCalidad, $proveedor->id, $antes->id, '2023-12-28', 4.0);

    // Esta semana entrega leche limpia y luego reincide.
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 80.0);
    $reincide = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-05', 50.0);
    $analisis = analizar($registrarCalidad, $proveedor->id, $reincide->id, '2024-01-05', 3.0);

    $generados = $generarPlanilla->ejecutar('2024-01-04');

    // Aparece en la planilla con S/ 0.00 en lugar de desaparecer.
    expect($analisis->sancionAplicada)->toBe('suspension_semana')
        ->and($generados)->toHaveCount(1)
        ->and($generados[0]->totalLitros)->toBe(0.0)
        ->and($generados[0]->totalPagar)->toBe(0.0);
});

test('la suspension solo dura esa semana y la siguiente cobra normal', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla, $registrarCalidad] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '667788');

    $primera = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2023-12-28', 30.0);
    analizar($registrarCalidad, $proveedor->id, $primera->id, '2023-12-28', 4.0);
    $reincide = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-05', 50.0);
    analizar($registrarCalidad, $proveedor->id, $reincide->id, '2024-01-05', 3.0);

    // Semana siguiente (jueves 11 a miercoles 17): vuelve a entregar y cobra.
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-12', 70.0);

    $generados = $generarPlanilla->ejecutar('2024-01-12');

    expect($generados)->toHaveCount(1)
        ->and($generados[0]->totalLitros)->toBe(70.0)
        ->and($generados[0]->totalPagar)->toBe(119.0);
});

test('un acopio que llega tarde desde el telefono entra en la planilla si aun no se pago', function () {
    // La app movil sincroniza hasta dias despues. Antes, al regenerar se
    // saltaba a quien ya tenia pago esa semana y esos litros no se pagaban.
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '889900');
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 100.0);
    $generarPlanilla->ejecutar('2024-01-04');

    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-06', 50.0);
    $regenerados = $generarPlanilla->ejecutar('2024-01-04');

    expect($regenerados)->toHaveCount(1)
        ->and($regenerados[0]->totalLitros)->toBe(150.0)
        ->and($regenerados[0]->totalPagar)->toBe(255.0);
});

test('regenerar conserva el precio con el que se genero esa semana', function () {
    [$proveedorRepo, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '112233', 1.70);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 100.0);
    $generarPlanilla->ejecutar('2024-01-04');

    // Cambia la temporada despues de generar la planilla.
    $proveedorRepo->guardar($proveedorRepo->buscarPorId($proveedor->id)->cambiarPrecioLitro(new \App\Domain\Proveedor\ValueObjects\PrecioLitro(2.50)));

    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-06', 10.0);
    $regenerados = $generarPlanilla->ejecutar('2024-01-04');

    expect($regenerados[0]->precioLitro)->toBe(1.70)
        ->and($regenerados[0]->totalPagar)->toBe(187.0);
});

test('si la planilla ya se pago, los litros que llegan tarde van en un pago complementario', function () {
    [, , $pagoRepo, $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '445599', precio: 1.70);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 100.0);
    $planilla = $generarPlanilla->ejecutar('2024-01-04')[0];
    $pagoRepo->guardar($pagoRepo->buscarPorId($planilla->id)->marcarComoPagado(new DateTimeImmutable('2024-01-12')));

    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-06', 50.0);
    $generados = $generarPlanilla->ejecutar('2024-01-04');

    // La planilla pagada no se toca; lo nuevo va aparte, al precio de esa semana.
    expect($pagoRepo->buscarPorId($planilla->id)->totalLitros->valor)->toBe(100.0)
        ->and($generados)->toHaveCount(1)
        ->and($generados[0]->secuencia)->toBe(1)
        ->and($generados[0]->totalLitros)->toBe(50.0)
        ->and($generados[0]->precioLitro)->toBe(1.70)
        ->and($generados[0]->totalPagar)->toBe(85.0)
        ->and($generados[0]->estado)->toBe('pendiente');

    // Volver a generar sin litros nuevos no duplica el complementario.
    expect($generarPlanilla->ejecutar('2024-01-04'))->toHaveCount(0);
});

test('un complementario pendiente se rehace y uno ya pagado abre el siguiente', function () {
    [, , $pagoRepo, $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '445600', precio: 2.00);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 100.0);
    $planilla = $generarPlanilla->ejecutar('2024-01-04')[0];
    $pagoRepo->guardar($pagoRepo->buscarPorId($planilla->id)->marcarComoPagado(new DateTimeImmutable('2024-01-12')));

    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-05', 20.0);
    $complemento = $generarPlanilla->ejecutar('2024-01-04')[0];

    // Llega otro acopio antes de pagar el complementario: se suma a ese mismo.
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-06', 10.0);
    $rehecho = $generarPlanilla->ejecutar('2024-01-04')[0];

    expect($rehecho->id)->toBe($complemento->id)
        ->and($rehecho->totalLitros)->toBe(30.0)
        ->and($rehecho->totalPagar)->toBe(60.0);

    // Pagado el complementario, un acopio mas tarde abre el complementario 2.
    $pagoRepo->guardar($pagoRepo->buscarPorId($rehecho->id)->marcarComoPagado(new DateTimeImmutable('2024-01-19')));
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-07', 5.0);
    $segundo = $generarPlanilla->ejecutar('2024-01-04')[0];

    expect($segundo->secuencia)->toBe(2)
        ->and($segundo->totalLitros)->toBe(5.0)
        ->and($pagoRepo->listarPorProveedorYSemana($proveedor->id, new DateTimeImmutable('2024-01-04')))->toHaveCount(3);
});

test('si una sancion llega despues de pagar, no se genera un pago negativo', function () {
    [, , $pagoRepo, $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla, $registrarCalidad] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '445601');
    $acopio = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 100.0);
    $planilla = $generarPlanilla->ejecutar('2024-01-04')[0];
    $pagoRepo->guardar($pagoRepo->buscarPorId($planilla->id)->marcarComoPagado(new DateTimeImmutable('2024-01-12')));

    analizar($registrarCalidad, $proveedor->id, $acopio->id, '2024-01-04', 5.0);

    expect($generarPlanilla->ejecutar('2024-01-04'))->toHaveCount(0)
        ->and($pagoRepo->listarPorProveedorYSemana($proveedor->id, new DateTimeImmutable('2024-01-04')))->toHaveCount(1);
});

test('una sancion por agua registrada despues de generar la planilla la corrige si no se pago', function () {
    [, , , $registrarProveedor, $registrarAcopio, $sincronizarAcopio, $generarPlanilla, $registrarCalidad] = crearContextoPlanilla();

    $proveedor = proveedorDePlanilla($registrarProveedor, '667700');
    $conAgua = acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-04', 40.0);
    acopioSincronizado($registrarAcopio, $sincronizarAcopio, $proveedor->id, '2024-01-05', 60.0);
    $generarPlanilla->ejecutar('2024-01-04');

    analizar($registrarCalidad, $proveedor->id, $conAgua->id, '2024-01-04', 5.0);
    $regenerados = $generarPlanilla->ejecutar('2024-01-04');

    expect($regenerados[0]->totalLitros)->toBe(60.0);
});
