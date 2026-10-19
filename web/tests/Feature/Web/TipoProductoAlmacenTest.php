<?php

declare(strict_types=1);

use App\Application\Inventario\DTOs\RegistrarEntradaInsumoData;
use App\Application\Inventario\DTOs\RegistrarProduccionProductoData;
use App\Application\Inventario\DTOs\RegistrarSalidaInsumoData;
use App\Application\Inventario\DTOs\RegistrarVentaData;
use App\Application\Inventario\UseCases\RegistrarEntradaInsumoUseCase;
use App\Application\Inventario\UseCases\RegistrarProduccionProductoUseCase;
use App\Application\Inventario\UseCases\RegistrarSalidaInsumoUseCase;
use App\Application\Inventario\UseCases\RegistrarVentaUseCase;
use App\Domain\Inventario\Exceptions\TipoProductoIncorrectoException;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;

/**
 * @return array{sal: ProductoModel, queso: ProductoModel}
 */
function almacenConSalYQueso(): array
{
    $sal = ProductoModel::factory()->insumo()->create(['nombre' => 'Sal fina']);
    $queso = ProductoModel::factory()->create(['nombre' => 'Queso fresco', 'tipo' => 'producto_terminado']);

    foreach ([$sal, $queso] as $producto) {
        MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => 20]);
    }

    return ['sal' => $sal, 'queso' => $queso];
}

test('no se vende un insumo', function () {
    ['sal' => $sal] = almacenConSalYQueso();

    expect(fn () => app(RegistrarVentaUseCase::class)->ejecutar(new RegistrarVentaData(
        productoId: $sal->id,
        fecha: '2026-09-14',
        cantidad: 1,
        precioUnitario: 5,
        cliente: null,
        usuarioId: null,
    )))->toThrow(TipoProductoIncorrectoException::class, 'Sal fina no es un producto terminado: no se puede registrar en una venta.');
});

test('la transformacion no da entrada a un insumo', function () {
    ['sal' => $sal] = almacenConSalYQueso();

    expect(fn () => app(RegistrarProduccionProductoUseCase::class)->ejecutar(new RegistrarProduccionProductoData(
        productoId: $sal->id,
        fecha: '2026-09-14',
        cantidad: 10,
        litrosProcesados: 100,
        observaciones: null,
        usuarioId: null,
    )))->toThrow(TipoProductoIncorrectoException::class, 'Sal fina no es un producto terminado');
});

test('las entradas y salidas de almacen no tocan el producto terminado', function () {
    ['queso' => $queso] = almacenConSalYQueso();

    expect(fn () => app(RegistrarEntradaInsumoUseCase::class)->ejecutar(new RegistrarEntradaInsumoData(
        productoId: $queso->id,
        fecha: '2026-09-14',
        cantidad: 50,
        lote: null,
        motivo: null,
        usuarioId: null,
    )))->toThrow(TipoProductoIncorrectoException::class, 'Queso fresco no es un insumo: no se puede registrar en una entrada de insumos.');

    // Sacar queso como "salida de insumo" se saltaria las reservas de los pedidos confirmados.
    expect(fn () => app(RegistrarSalidaInsumoUseCase::class)->ejecutar(new RegistrarSalidaInsumoData(
        productoId: $queso->id,
        fecha: '2026-09-14',
        cantidad: 5,
        motivo: null,
        usuarioId: null,
    )))->toThrow(TipoProductoIncorrectoException::class, 'Queso fresco no es un insumo');

    expect(MovimientoInventarioModel::query()->where('producto_id', $queso->id)->count())->toBe(1);
});
