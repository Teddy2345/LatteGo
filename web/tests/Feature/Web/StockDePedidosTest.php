<?php

declare(strict_types=1);

use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Pedidos\Detalle;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/**
 * Los pedidos de la tienda nunca tocaban el almacen: se entregaban y el stock
 * seguia igual, asi que la tienda volvia a vender lo ya vendido. Ahora:
 * confirmar reserva, entregar descuenta, y la venta en planta respeta lo
 * reservado.
 */
function rolStock(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

function quesoConStock(float $cantidad): ProductoModel
{
    $queso = ProductoModel::factory()->create(['tipo' => 'producto_terminado', 'nombre' => 'Queso fresco', 'unidad' => 'pieza', 'precio_referencia' => 20]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => $cantidad]);

    return $queso;
}

function pedidoDeQueso(ProductoModel $queso, float $cantidad, string $estado = 'pendiente', ?int $repartidorId = null): PedidoModel
{
    $pedido = PedidoModel::factory()->create(['estado' => $estado, 'repartidor_id' => $repartidorId, 'cliente_nombre' => 'Rosa Quispe']);
    $pedido->items()->create([
        'producto_id' => $queso->id, 'nombre_producto' => $queso->nombre, 'cantidad' => $cantidad, 'precio_unitario' => 20,
    ]);

    return $pedido;
}

function stockDe(ProductoModel $producto): float
{
    return app(MovimientoInventarioRepository::class)->stockDe($producto->id);
}

test('entregar un pedido descuenta la mercaderia del almacen', function () {
    $queso = quesoConStock(10);
    $repartidor = rolStock('repartidor');
    $pedido = pedidoDeQueso($queso, 4, 'confirmado', $repartidor->id);

    Livewire::actingAs($repartidor)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->set('metodoPago', 'efectivo')
        ->set('montoCobrado', 80)
        ->set('fechaEntrega', '2026-09-12')
        ->call('entregar')
        ->assertSet('error', '');

    expect($pedido->fresh()->estado)->toBe('entregado')
        ->and(stockDe($queso))->toBe(6.0);
});

test('la venta web entregada aparece en el historial de ventas', function () {
    $queso = quesoConStock(10);
    $repartidor = rolStock('repartidor');
    $pedido = pedidoDeQueso($queso, 3, 'confirmado', $repartidor->id);

    Livewire::actingAs($repartidor)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->set('montoCobrado', 60)
        ->set('fechaEntrega', '2026-09-12')
        ->call('entregar');

    $venta = MovimientoInventarioModel::query()->where('tipo', 'venta')->where('producto_id', $queso->id)->first();

    expect($venta)->not->toBeNull()
        ->and((float) $venta->cantidad)->toBe(3.0)
        ->and((float) $venta->precio_unitario)->toBe(20.0)
        ->and($venta->cliente)->toBe('Rosa Quispe');
});

test('no se puede confirmar un pedido si lo libre no alcanza', function () {
    // Hay 10: uno confirmado ya reserva 8, asi que otro de 5 no entra.
    $queso = quesoConStock(10);
    pedidoDeQueso($queso, 8, 'confirmado');
    $segundo = pedidoDeQueso($queso, 5);

    Livewire::actingAs(rolStock('ventas'))
        ->test(Detalle::class, ['pedido' => $segundo->id])
        ->call('confirmar')
        ->assertSet('error', 'No alcanza el stock para confirmar: de Queso fresco quedan 2 libres y el pedido pide 5.');

    expect($segundo->fresh()->estado)->toBe('pendiente');
});

test('un pedido pendiente no reserva: pedidos falsos no pueden agotar el almacen', function () {
    $queso = quesoConStock(10);
    pedidoDeQueso($queso, 10);
    pedidoDeQueso($queso, 10);
    $real = pedidoDeQueso($queso, 6);

    Livewire::actingAs(rolStock('ventas'))
        ->test(Detalle::class, ['pedido' => $real->id])
        ->call('confirmar')
        ->assertSet('error', '');

    expect($real->fresh()->estado)->toBe('confirmado');
});

test('la venta en planta no puede vender lo reservado para un pedido confirmado', function () {
    $queso = quesoConStock(10);
    pedidoDeQueso($queso, 8, 'confirmado');

    $this->actingAs(rolStock('ventas'));

    expect(fn () => app(\App\Application\Inventario\UseCases\RegistrarVentaUseCase::class)->ejecutar(
        new \App\Application\Inventario\DTOs\RegistrarVentaData(
            productoId: $queso->id,
            fecha: '2026-09-12',
            cantidad: 5,
            precioUnitario: 20,
            cliente: 'Mostrador',
            usuarioId: null,
        ),
    ))->toThrow(\App\Domain\Inventario\Exceptions\StockInsuficienteException::class, 'Solo quedan 2 pieza de Queso fresco libres: el resto está reservado para pedidos confirmados.');
});

test('cancelar un pedido confirmado libera lo reservado', function () {
    $queso = quesoConStock(10);
    $confirmado = pedidoDeQueso($queso, 8, 'confirmado');

    Livewire::actingAs(rolStock('ventas'))
        ->test(Detalle::class, ['pedido' => $confirmado->id])
        ->call('cancelar');

    expect($confirmado->fresh()->estado)->toBe('cancelado')
        ->and(app(\App\Domain\Pedidos\Repositories\PedidoRepository::class)->cantidadReservada($queso->id))->toBe(0.0)
        ->and(stockDe($queso))->toBe(10.0);
});

test('en la tienda no se puede pedir lo que ya esta reservado', function () {
    $queso = quesoConStock(10);
    pedidoDeQueso($queso, 8, 'confirmado');

    Livewire::test(\App\Presentation\Web\Livewire\Tienda\Catalogo::class)
        ->set("cantidades.{$queso->id}", 5)
        ->call('agregar', $queso->id);

    Livewire::test(\App\Presentation\Web\Livewire\Tienda\Checkout::class)
        ->set('nombre', 'Maria')
        ->set('telefono', '71234567')
        ->set('direccion', 'Calle 1')
        ->call('confirmar')
        ->assertSee('Solo hay 2');
});

test('un pedido ya entregado no se puede cancelar', function () {
    $queso = quesoConStock(10);
    $entregado = pedidoDeQueso($queso, 4, 'entregado');

    Livewire::actingAs(rolStock('ventas'))
        ->test(Detalle::class, ['pedido' => $entregado->id])
        ->call('cancelar')
        ->assertSet('error', 'Solo se puede cancelar un pedido pendiente o confirmado.');

    expect($entregado->fresh()->estado)->toBe('entregado');
});
