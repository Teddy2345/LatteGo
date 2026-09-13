<?php

declare(strict_types=1);

use App\Application\Inventario\DTOs\RegistrarEntradaInsumoData;
use App\Application\Inventario\DTOs\RegistrarProductoData;
use App\Application\Inventario\UseCases\RegistrarEntradaInsumoUseCase;
use App\Application\Inventario\UseCases\RegistrarProductoUseCase;
use App\Application\Produccion\DTOs\InsumoUtilizadoData;
use App\Application\Produccion\DTOs\RegistrarProduccionData;
use App\Application\Produccion\UseCases\RegistrarProduccionUseCase;
use App\Domain\Inventario\Exceptions\StockInsuficienteException;
use Tests\Support\InMemoryMovimientoInventarioRepository;
use Tests\Support\InMemoryProduccionRepository;
use Tests\Support\InMemoryProductoRepository;
use Tests\Support\InMemoryTransactionManager;

/**
 * @return array{0: RegistrarProduccionUseCase, 1: InMemoryProduccionRepository, 2: InMemoryProductoRepository, 3: InMemoryMovimientoInventarioRepository}
 */
function contextoIntegracionProduccion(): array
{
    $produccionRepo = new InMemoryProduccionRepository();
    $productoRepo = new InMemoryProductoRepository();
    $movimientoRepo = new InMemoryMovimientoInventarioRepository();

    $registrar = new RegistrarProduccionUseCase(
        $produccionRepo,
        $movimientoRepo,
        $productoRepo,
        new InMemoryTransactionManager(),
    );

    return [$registrar, $produccionRepo, $productoRepo, $movimientoRepo];
}

test('sin producto ni insumos, la produccion se guarda igual que antes de la integracion', function () {
    [$registrar, , , $movimientoRepo] = contextoIntegracionProduccion();

    $resultado = $registrar->ejecutar(new RegistrarProduccionData(
        fecha: '2026-09-13',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
    ));

    expect($resultado->quesosProducidos)->toBe(12)
        ->and($movimientoRepo->stockPorProducto())->toBe([]);
});

test('conectar con un producto del catalogo aumenta su stock', function () {
    [$registrar, $produccionRepo, $productoRepo, $movimientoRepo] = contextoIntegracionProduccion();

    $queso = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Queso fresco',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'pieza',
        precioReferencia: 20,
        stockMinimo: null,
    ));

    $resultado = $registrar->ejecutar(new RegistrarProduccionData(
        fecha: '2026-09-13',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
        productoId: $queso->id,
    ));

    expect($movimientoRepo->stockDe($queso->id))->toBe(12.0)
        ->and($produccionRepo->buscarPorId($resultado->id)->productoId)->toBe($queso->id);
});

test('los insumos utilizados se descuentan del almacen en la misma operacion', function () {
    [$registrar, , $productoRepo, $movimientoRepo] = contextoIntegracionProduccion();

    $cuajo = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Cuajo',
        tipo: 'insumo',
        categoria: null,
        unidad: 'litro',
        precioReferencia: 0,
        stockMinimo: null,
    ));
    (new RegistrarEntradaInsumoUseCase($movimientoRepo, $productoRepo))->ejecutar(new RegistrarEntradaInsumoData(
        productoId: $cuajo->id,
        fecha: '2026-09-01',
        cantidad: 10,
        lote: null,
        motivo: null,
        usuarioId: null,
    ));

    $registrar->ejecutar(new RegistrarProduccionData(
        fecha: '2026-09-13',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
        insumosUtilizados: [new InsumoUtilizadoData(productoId: $cuajo->id, cantidad: 3)],
    ));

    expect($movimientoRepo->stockDe($cuajo->id))->toBe(7.0);
});

test('si falta stock de un insumo, la operacion lanza StockInsuficienteException', function () {
    // La garantia de "todo o nada" (que tambien deshaga la Produccion ya
    // guardada) depende de una transaccion de base de datos real: se
    // verifica en Tests\Feature\Web\ProduccionAlmacenWebTest, no aqui, porque
    // el InMemoryTransactionManager de estas pruebas no revierte nada, solo
    // ejecuta el callback.
    [$registrar, , $productoRepo] = contextoIntegracionProduccion();

    $cuajo = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Cuajo',
        tipo: 'insumo',
        categoria: null,
        unidad: 'litro',
        precioReferencia: 0,
        stockMinimo: null,
    ));
    // Sin ninguna entrada previa: 0 en stock.

    expect(fn () => $registrar->ejecutar(new RegistrarProduccionData(
        fecha: '2026-09-13',
        litrosProcesados: 100.0,
        quesosProducidos: 12,
        jefaProduccionId: null,
        observaciones: null,
        insumosUtilizados: [new InsumoUtilizadoData(productoId: $cuajo->id, cantidad: 3)],
    )))->toThrow(StockInsuficienteException::class);
});
