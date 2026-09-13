<?php

declare(strict_types=1);

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Acopio\UseCases\SincronizarAcopioUseCase;
use App\Application\Costeo\UseCases\CalcularCostoProduccionUseCase;
use App\Application\Inventario\DTOs\RegistrarEntradaInsumoData;
use App\Application\Inventario\DTOs\RegistrarProductoData;
use App\Application\Inventario\UseCases\RegistrarEntradaInsumoUseCase;
use App\Application\Inventario\UseCases\RegistrarProductoUseCase;
use App\Application\Produccion\DTOs\InsumoUtilizadoData;
use App\Application\Produccion\DTOs\RegistrarProduccionData;
use App\Application\Produccion\UseCases\RegistrarProduccionUseCase;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use App\Domain\Produccion\Exceptions\ProduccionNoEncontradaException;
use Tests\Support\InMemoryAcopioRepository;
use Tests\Support\InMemoryMovimientoInventarioRepository;
use Tests\Support\InMemoryProduccionRepository;
use Tests\Support\InMemoryProductoRepository;
use Tests\Support\InMemoryProveedorRepository;
use Tests\Support\InMemoryTransactionManager;

/**
 * @return array{
 *     0: RegistrarProveedorUseCase, 1: RegistrarAcopioUseCase, 2: SincronizarAcopioUseCase,
 *     3: RegistrarProductoUseCase, 4: RegistrarEntradaInsumoUseCase, 5: RegistrarProduccionUseCase,
 *     6: CalcularCostoProduccionUseCase,
 * }
 */
function crearContextoCosteo(): array
{
    $proveedorRepo = new InMemoryProveedorRepository();
    $acopioRepo = new InMemoryAcopioRepository();
    $productoRepo = new InMemoryProductoRepository();
    $movimientoRepo = new InMemoryMovimientoInventarioRepository();
    $produccionRepo = new InMemoryProduccionRepository();

    return [
        new RegistrarProveedorUseCase($proveedorRepo),
        new RegistrarAcopioUseCase($acopioRepo),
        new SincronizarAcopioUseCase($acopioRepo),
        new RegistrarProductoUseCase($productoRepo),
        new RegistrarEntradaInsumoUseCase($movimientoRepo, $productoRepo),
        new RegistrarProduccionUseCase($produccionRepo, $movimientoRepo, $productoRepo, new InMemoryTransactionManager()),
        new CalcularCostoProduccionUseCase($produccionRepo, $movimientoRepo, $productoRepo, $acopioRepo, $proveedorRepo),
    ];
}

test('el costo de leche es el precio por litro del proveedor aplicado a los litros procesados', function () {
    [$registrarProveedor, $registrarAcopio, $sincronizar, , , $registrarProduccion, $calcular] = crearContextoCosteo();

    $proveedor = $registrarProveedor->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Uno',
        cedula: '111000',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 3.5,
        rutaId: null,
    ));
    $acopio = $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $proveedor->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-04',
        cantidadLitros: 100.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $sincronizar->ejecutar($acopio->id);

    $produccion = $registrarProduccion->ejecutar(new RegistrarProduccionData(
        fecha: '2024-01-05',
        litrosProcesados: 50.0,
        quesosProducidos: 6,
        jefaProduccionId: null,
        observaciones: null,
    ));

    $costo = $calcular->ejecutar($produccion->id);

    expect($costo->precioLechePromedioPonderado)->toBe(3.5)
        ->and($costo->costoLeche)->toBe(175.0)
        ->and($costo->costoTotal)->toBe(175.0)
        ->and($costo->costoPorQueso)->toBe(round(175.0 / 6, 2));
});

test('el precio de la leche se pondera entre los proveedores que acopiaron esa semana', function () {
    [$registrarProveedor, $registrarAcopio, $sincronizar, , , $registrarProduccion, $calcular] = crearContextoCosteo();

    $barato = $registrarProveedor->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Barato',
        cedula: '222000',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 2.0,
        rutaId: null,
    ));
    $caro = $registrarProveedor->ejecutar(new RegistrarProveedorData(
        nombre: 'Proveedor Caro',
        cedula: '333000',
        telefono: null,
        finca: null,
        litrosProm: 50,
        precioLitro: 4.0,
        rutaId: null,
    ));

    $a1 = $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $barato->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-04',
        cantidadLitros: 300.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $a2 = $registrarAcopio->ejecutar(new RegistrarAcopioData(
        proveedorId: $caro->id,
        acopiadorId: null,
        rutaId: null,
        fecha: '2024-01-05',
        cantidadLitros: 100.0,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));
    $sincronizar->ejecutar($a1->id);
    $sincronizar->ejecutar($a2->id);

    // Precio ponderado: (300*2 + 100*4) / 400 = 2.50
    $produccion = $registrarProduccion->ejecutar(new RegistrarProduccionData(
        fecha: '2024-01-06',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
    ));

    $costo = $calcular->ejecutar($produccion->id);

    expect($costo->precioLechePromedioPonderado)->toBe(2.5)
        ->and($costo->costoLeche)->toBe(250.0);
});

test('sin acopios sincronizados esa semana, el costo de leche y el total no estan disponibles', function () {
    [, , , , , $registrarProduccion, $calcular] = crearContextoCosteo();

    $produccion = $registrarProduccion->ejecutar(new RegistrarProduccionData(
        fecha: '2024-02-01',
        litrosProcesados: 80.0,
        quesosProducidos: 9,
        jefaProduccionId: null,
        observaciones: null,
    ));

    $costo = $calcular->ejecutar($produccion->id);

    expect($costo->precioLechePromedioPonderado)->toBeNull()
        ->and($costo->costoLeche)->toBeNull()
        ->and($costo->costoTotal)->toBeNull();
});

test('el costo de insumos usa el costo promedio ponderado de sus compras registradas', function () {
    [, , , $registrarProducto, $registrarEntrada, $registrarProduccion, $calcular] = crearContextoCosteo();

    $cuajo = $registrarProducto->ejecutar(new RegistrarProductoData(
        nombre: 'Cuajo',
        tipo: 'insumo',
        categoria: null,
        unidad: 'litro',
        precioReferencia: 0,
        stockMinimo: null,
    ));
    // Dos compras a distinto costo: promedio ponderado = (10*2 + 10*4) / 20 = 3.0
    $registrarEntrada->ejecutar(new RegistrarEntradaInsumoData(
        productoId: $cuajo->id,
        fecha: '2024-01-01',
        cantidad: 10,
        lote: null,
        motivo: null,
        usuarioId: null,
        costoUnitario: 2.0,
    ));
    $registrarEntrada->ejecutar(new RegistrarEntradaInsumoData(
        productoId: $cuajo->id,
        fecha: '2024-01-02',
        cantidad: 10,
        lote: null,
        motivo: null,
        usuarioId: null,
        costoUnitario: 4.0,
    ));

    $produccion = $registrarProduccion->ejecutar(new RegistrarProduccionData(
        fecha: '2024-01-05',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
        insumosUtilizados: [new InsumoUtilizadoData(productoId: $cuajo->id, cantidad: 5)],
    ));

    $costo = $calcular->ejecutar($produccion->id);

    expect($costo->costoInsumosConocido)->toBe(15.0)
        ->and($costo->costoInsumosIncompleto)->toBeFalse()
        ->and($costo->insumos[0]->costoUnitario)->toBe(3.0);
});

test('un insumo sin costo unitario registrado deja el costeo marcado como incompleto', function () {
    [, , , $registrarProducto, $registrarEntrada, $registrarProduccion, $calcular] = crearContextoCosteo();

    $sal = $registrarProducto->ejecutar(new RegistrarProductoData(
        nombre: 'Sal fina',
        tipo: 'insumo',
        categoria: null,
        unidad: 'kilo',
        precioReferencia: 0,
        stockMinimo: null,
    ));
    $registrarEntrada->ejecutar(new RegistrarEntradaInsumoData(
        productoId: $sal->id,
        fecha: '2024-01-01',
        cantidad: 20,
        lote: null,
        motivo: null,
        usuarioId: null,
    ));

    $produccion = $registrarProduccion->ejecutar(new RegistrarProduccionData(
        fecha: '2024-01-05',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
        insumosUtilizados: [new InsumoUtilizadoData(productoId: $sal->id, cantidad: 5)],
    ));

    $costo = $calcular->ejecutar($produccion->id);

    expect($costo->costoInsumosIncompleto)->toBeTrue()
        ->and($costo->costoInsumosConocido)->toBe(0.0)
        ->and($costo->insumos[0]->costoUnitario)->toBeNull()
        ->and($costo->insumos[0]->costoTotal)->toBeNull();
});

test('lanza excepcion si la produccion no existe', function () {
    [, , , , , , $calcular] = crearContextoCosteo();

    $calcular->ejecutar(999);
})->throws(ProduccionNoEncontradaException::class);
