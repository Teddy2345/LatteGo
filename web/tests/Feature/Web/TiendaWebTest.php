<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Pedidos\Detalle as PedidoDetalle;
use App\Presentation\Web\Livewire\Pedidos\Index as PedidosIndex;
use App\Presentation\Web\Livewire\Tienda\Carrito;
use App\Presentation\Web\Livewire\Tienda\Catalogo;
use App\Presentation\Web\Livewire\Tienda\Checkout;
use App\Presentation\Web\Livewire\Tienda\Confirmacion;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioVentasPedidos(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('ventas');

    return $user;
}

test('la tienda publica muestra los productos terminados con stock', function () {
    $queso = ProductoModel::factory()->create(['tipo' => 'producto_terminado', 'nombre' => 'Queso fresco', 'precio_referencia' => 22]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => 15]);
    ProductoModel::factory()->insumo()->create(['nombre' => 'Cuajo']);

    Livewire::test(Catalogo::class)
        ->assertSee('Queso fresco')
        ->assertDontSee('Cuajo');
});

test('agregar al carrito sin sesion y pagar crea un pedido pendiente', function () {
    $queso = ProductoModel::factory()->create(['tipo' => 'producto_terminado', 'nombre' => 'Queso fresco', 'precio_referencia' => 22]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => 15]);

    Livewire::test(Catalogo::class)
        ->set("cantidades.{$queso->id}", 3)
        ->call('agregar', $queso->id)
        ->assertSee('Se agregó al carrito.');

    Livewire::test(Carrito::class)
        ->assertSee('Queso fresco')
        ->assertSee('66.00'); // 3 * 22

    Livewire::test(Checkout::class)
        ->set('nombre', 'Maria Perez')
        ->set('telefono', '71234567')
        ->set('direccion', 'Calle Sucre 123')
        ->call('confirmar')
        ->assertRedirect();

    $this->assertDatabaseHas('pedidos', [
        'cliente_nombre' => 'Maria Perez',
        'estado' => 'pendiente',
    ]);
    $this->assertDatabaseHas('items_pedido', [
        'producto_id' => $queso->id,
        'cantidad' => 3.00,
    ]);
});

test('no se puede pedir mas de lo que hay en existencia desde la tienda', function () {
    $queso = ProductoModel::factory()->create(['tipo' => 'producto_terminado', 'nombre' => 'Queso fresco', 'precio_referencia' => 22]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => 2]);

    Livewire::test(Catalogo::class)
        ->set("cantidades.{$queso->id}", 10)
        ->call('agregar', $queso->id);

    Livewire::test(Checkout::class)
        ->set('nombre', 'Maria Perez')
        ->set('telefono', '71234567')
        ->set('direccion', 'Calle Sucre 123')
        ->call('confirmar')
        ->assertSee('Solo hay 2');

    expect(PedidoModel::count())->toBe(0);
});

test('un producto sin existencia se muestra agotado en lugar del boton de agregar', function () {
    ProductoModel::factory()->create(['tipo' => 'producto_terminado', 'nombre' => 'Queso fresco']);

    Livewire::test(Catalogo::class)
        ->assertSee('Agotado')
        ->assertDontSee('Agregar');
});

test('ventas ve la bandeja de pedidos y puede confirmar uno pendiente', function () {
    $pedido = PedidoModel::factory()->create(['cliente_nombre' => 'Cliente Web']);
    $producto = ProductoModel::factory()->create(['tipo' => 'producto_terminado']);
    $pedido->items()->create(['producto_id' => $producto->id, 'nombre_producto' => $producto->nombre, 'cantidad' => 2, 'precio_unitario' => 10]);

    Livewire::actingAs(usuarioVentasPedidos())
        ->test(PedidosIndex::class)
        ->assertSee('Cliente Web');

    Livewire::actingAs(usuarioVentasPedidos())
        ->test(PedidoDetalle::class, ['pedido' => $pedido->id])
        ->call('confirmar')
        ->assertSet('error', '');

    expect($pedido->fresh()->estado)->toBe('confirmado');
});

test('un rol sin ventas.registrar ni reportes.ver no puede ver los pedidos', function () {
    app(RolePermissionSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('acopiador');

    Livewire::actingAs($user)->test(PedidosIndex::class)->assertForbidden();
});

test('quien compra si ve la pantalla de gracias de su propio pedido', function () {
    $queso = ProductoModel::factory()->create(['tipo' => 'producto_terminado', 'nombre' => 'Queso fresco', 'precio_referencia' => 22]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => 15]);

    Livewire::test(Catalogo::class)
        ->set("cantidades.{$queso->id}", 1)
        ->call('agregar', $queso->id);

    Livewire::test(Checkout::class)
        ->set('nombre', 'Maria Perez')
        ->set('telefono', '71234567')
        ->set('direccion', 'Calle Sucre 123')
        ->call('confirmar');

    $pedido = PedidoModel::query()->firstOrFail();

    Livewire::test(Confirmacion::class, ['pedido' => $pedido->id])
        ->assertOk()
        ->assertSee('Maria Perez');
});

test('un visitante cualquiera no puede leer la pantalla de gracias de otro cliente', function () {
    // Los datos del cliente (nombre y telefono) quedarian expuestos a quien
    // solo pruebe numeros de pedido en la URL publica de la tienda.
    $pedido = PedidoModel::factory()->create(['cliente_nombre' => 'Maria Perez', 'cliente_telefono' => '71234567']);

    Livewire::test(Confirmacion::class, ['pedido' => $pedido->id])
        ->assertForbidden();
});
