<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Inventario\Formulario as TransformacionFormulario;
use App\Presentation\Web\Livewire\Inventario\Index as AlmacenIndex;
use App\Presentation\Web\Livewire\Ventas\Formulario as VentaFormulario;
use App\Presentation\Web\Livewire\Ventas\Index as VentasIndex;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioWeb(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('el almacen muestra el stock de cada producto', function () {
    $producto = ProductoModel::factory()->create(['nombre' => 'Queso fresco', 'unidad' => 'pieza']);
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 80]);
    MovimientoInventarioModel::factory()->venta()->create(['producto_id' => $producto->id, 'cantidad' => 30]);

    Livewire::actingAs(usuarioWeb('produccion'))
        ->test(AlmacenIndex::class)
        ->assertOk()
        ->assertSee('Queso fresco')
        ->assertSee('50.00');
});

test('el almacen queda cerrado para quien no ve inventario', function () {
    Livewire::actingAs(usuarioWeb('acopiador'))
        ->test(AlmacenIndex::class)
        ->assertForbidden();
});

test('registrar una transformacion desde el portal aumenta el stock', function () {
    $producto = ProductoModel::factory()->create();

    Livewire::actingAs(usuarioWeb('produccion'))
        ->test(TransformacionFormulario::class)
        ->set('productoId', $producto->id)
        ->set('fecha', '2026-09-07')
        ->set('cantidad', 20)
        ->set('litrosProcesados', 160)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('inventario.index'));

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $producto->id,
        'tipo' => 'produccion',
        'cantidad' => 20.00,
    ]);
});

test('el formulario de venta propone el precio de referencia del producto', function () {
    $producto = ProductoModel::factory()->create(['precio_referencia' => 22.50]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 40]);

    Livewire::actingAs(usuarioWeb('admin'))
        ->test(VentaFormulario::class)
        ->set('productoId', $producto->id)
        ->assertSet('precioUnitario', 22.50);
});

test('registrar una venta desde el portal descuenta del almacen', function () {
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 40]);

    Livewire::actingAs(usuarioWeb('admin'))
        ->test(VentaFormulario::class)
        ->set('productoId', $producto->id)
        ->set('fecha', '2026-09-07')
        ->set('cantidad', 15)
        ->set('precioUnitario', 20)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('ventas.index'));

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $producto->id,
        'tipo' => 'venta',
        'total' => 300.00,
    ]);
});

test('el portal avisa cuando la venta supera el stock en lugar de fallar', function () {
    $producto = ProductoModel::factory()->create(['nombre' => 'Yogurt natural', 'unidad' => 'litro']);
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 5]);

    Livewire::actingAs(usuarioWeb('admin'))
        ->test(VentaFormulario::class)
        ->set('productoId', $producto->id)
        ->set('fecha', '2026-09-07')
        ->set('cantidad', 12)
        ->set('precioUnitario', 12)
        ->call('guardar')
        ->assertNoRedirect()
        ->assertSee('Solo hay 5 litro de Yogurt natural en existencia.');

    expect(MovimientoInventarioModel::query()->where('tipo', 'venta')->count())->toBe(0);
});

test('el operador de planta no puede abrir el formulario de ventas', function () {
    Livewire::actingAs(usuarioWeb('produccion'))
        ->test(VentaFormulario::class)
        ->assertForbidden();
});

test('el operador de planta tampoco ve el historial comercial', function () {
    // Ve el almacen porque necesita el stock, pero no los precios ni los clientes.
    Livewire::actingAs(usuarioWeb('produccion'))
        ->test(VentasIndex::class)
        ->assertForbidden();
});

test('el supervisor consulta las ventas como parte de sus reportes', function () {
    Livewire::actingAs(usuarioWeb('supervisor'))
        ->test(VentasIndex::class)
        ->assertOk();
});

test('la encargada de ventas vende y consulta el almacen', function () {
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 60]);

    $encargada = usuarioWeb('ventas');

    Livewire::actingAs($encargada)->test(VentasIndex::class)->assertOk();
    Livewire::actingAs($encargada)->test(AlmacenIndex::class)->assertOk();

    Livewire::actingAs($encargada)
        ->test(VentaFormulario::class)
        ->set('productoId', $producto->id)
        ->set('fecha', '2026-09-07')
        ->set('cantidad', 10)
        ->set('precioUnitario', 18)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('ventas.index'));

    $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'venta', 'total' => 180.00]);
});

test('la encargada de ventas no transforma leche', function () {
    Livewire::actingAs(usuarioWeb('ventas'))
        ->test(TransformacionFormulario::class)
        ->assertForbidden();
});

test('el listado de ventas suma lo comercializado', function () {
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 100]);
    MovimientoInventarioModel::factory()->venta()->create([
        'producto_id' => $producto->id,
        'cantidad' => 10,
        'precio_unitario' => 15,
        'total' => 150,
        'cliente' => 'Mercado Rodriguez',
    ]);

    Livewire::actingAs(usuarioWeb('admin'))
        ->test(VentasIndex::class)
        ->assertOk()
        ->assertSee('Mercado Rodriguez')
        ->assertSee('150.00');
});
