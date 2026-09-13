<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Pedidos\Comprobante;
use App\Presentation\Web\Livewire\Pedidos\Detalle;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioConRolReparto(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

function pedidoConfirmadoDePrueba(): PedidoModel
{
    $producto = ProductoModel::factory()->create(['tipo' => 'producto_terminado']);
    $pedido = PedidoModel::factory()->confirmado()->create(['cliente_nombre' => 'Cliente Web']);
    $pedido->items()->create([
        'producto_id' => $producto->id, 'nombre_producto' => $producto->nombre, 'cantidad' => 2, 'precio_unitario' => 20,
    ]);

    return $pedido;
}

test('ventas asigna un repartidor a un pedido confirmado', function () {
    $ventas = usuarioConRolReparto('ventas');
    $repartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();

    Livewire::actingAs($ventas)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->set('repartidorId', $repartidor->id)
        ->call('asignar')
        ->assertSet('error', '');

    expect($pedido->fresh()->repartidor_id)->toBe($repartidor->id);
});

test('el repartidor entrega el pedido y registra el cobro', function () {
    $ventas = usuarioConRolReparto('ventas');
    $repartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();
    $pedido->update(['repartidor_id' => $repartidor->id]);

    Livewire::actingAs($repartidor)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->set('metodoPago', 'efectivo')
        ->set('montoCobrado', 40)
        ->set('fechaEntrega', '2026-09-15')
        ->call('entregar')
        ->assertSet('error', '');

    $pedido->refresh();
    expect($pedido->estado)->toBe('entregado')
        ->and($pedido->metodo_pago)->toBe('efectivo')
        ->and((float) $pedido->monto_cobrado)->toBe(40.0);
});

test('el repartidor no puede asignar repartidores, solo entregar', function () {
    $repartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();
    $pedido->update(['repartidor_id' => $repartidor->id]);

    Livewire::actingAs($repartidor)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->set('repartidorId', $repartidor->id)
        ->call('asignar')
        ->assertForbidden();
});

test('el repartidor no puede abrir el pedido de otro repartidor', function () {
    $repartidor = usuarioConRolReparto('repartidor');
    $otroRepartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();
    $pedido->update(['repartidor_id' => $otroRepartidor->id]);

    Livewire::actingAs($repartidor)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->assertForbidden();
});

test('el repartidor no puede abrir un pedido que todavia no tiene repartidor', function () {
    $repartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();

    Livewire::actingAs($repartidor)
        ->test(Detalle::class, ['pedido' => $pedido->id])
        ->assertForbidden();
});

test('el repartidor no puede imprimir el comprobante de un pedido ajeno', function () {
    $repartidor = usuarioConRolReparto('repartidor');
    $otroRepartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();
    $pedido->update(['repartidor_id' => $otroRepartidor->id]);

    Livewire::actingAs($repartidor)
        ->test(Comprobante::class, ['pedido' => $pedido->id])
        ->assertForbidden();
});

test('el repartidor si imprime el comprobante del pedido que el entrego', function () {
    $repartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();
    $pedido->update([
        'repartidor_id' => $repartidor->id, 'estado' => 'entregado',
        'metodo_pago' => 'efectivo', 'monto_cobrado' => 40, 'fecha_entrega' => '2026-09-15',
    ]);

    Livewire::actingAs($repartidor)
        ->test(Comprobante::class, ['pedido' => $pedido->id])
        ->assertOk()
        ->assertSee('Cliente Web');
});

test('el repartidor solo ve los pedidos que tiene asignados', function () {
    $repartidor = usuarioConRolReparto('repartidor');
    $otroRepartidor = usuarioConRolReparto('repartidor');

    $asignado = pedidoConfirmadoDePrueba();
    $asignado->update(['repartidor_id' => $repartidor->id]);
    $ajeno = pedidoConfirmadoDePrueba();
    $ajeno->update(['repartidor_id' => $otroRepartidor->id, 'cliente_nombre' => 'Otro Cliente']);

    Livewire::actingAs($repartidor)
        ->test(\App\Presentation\Web\Livewire\Pedidos\Index::class)
        ->assertSee('Cliente Web')
        ->assertDontSee('Otro Cliente');
});

test('el comprobante muestra el metodo y monto cobrado una vez entregado', function () {
    $ventas = usuarioConRolReparto('ventas');
    $repartidor = usuarioConRolReparto('repartidor');
    $pedido = pedidoConfirmadoDePrueba();
    $pedido->update([
        'repartidor_id' => $repartidor->id, 'estado' => 'entregado',
        'metodo_pago' => 'qr', 'monto_cobrado' => 40, 'fecha_entrega' => '2026-09-15',
    ]);

    Livewire::actingAs($ventas)
        ->test(Comprobante::class, ['pedido' => $pedido->id])
        ->assertSee('Cliente Web')
        ->assertSee('qr')
        ->assertSee('40.00');
});

test('un rol sin acceso a pedidos no puede ver el comprobante', function () {
    app(RolePermissionSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('acopiador');
    $pedido = pedidoConfirmadoDePrueba();

    Livewire::actingAs($user)->test(Comprobante::class, ['pedido' => $pedido->id])->assertForbidden();
});
