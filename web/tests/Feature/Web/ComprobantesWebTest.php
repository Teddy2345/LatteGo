<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Ventas\Formulario as VentaFormulario;
use App\Presentation\Web\Livewire\Ventas\Index as VentasIndex;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioDeCaja(string $rol = 'ventas'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

/** Un producto con stock suficiente para venderlo. */
function productoDeCaja(string $nombre = 'Queso fresco', float $stock = 20): ProductoModel
{
    $producto = ProductoModel::factory()->create(['nombre' => $nombre, 'unidad' => 'pieza', 'precio_referencia' => 22]);

    MovimientoInventarioModel::factory()->create([
        'producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => $stock, 'litros_procesados' => 100,
    ]);

    return $producto;
}

test('la venta emite su boleta numerada y queda descargable en PDF', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    Livewire::actingAs($usuario)
        ->test(VentaFormulario::class)
        ->set('productoId', $producto->id)
        ->set('cantidad', 2)
        ->set('precioUnitario', 22)
        ->set('cliente', 'María Condori')
        ->set('tipoComprobante', 'boleta')
        ->set('clienteDocumento', '70123456')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('ventas.index'));

    $this->assertDatabaseHas('comprobantes', [
        'tipo' => 'boleta', 'serie' => 'B001', 'numero' => 1,
        'cliente_nombre' => 'María Condori', 'cliente_documento' => '70123456', 'total' => 44.00,
    ]);

    $id = DB::table('comprobantes')->value('id');

    $this->actingAs($usuario)->get(route('comprobantes.pdf', $id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('la numeracion avanza y no se repite entre boletas', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    foreach ([1, 2, 3] as $vez) {
        Livewire::actingAs($usuario)->test(VentaFormulario::class)
            ->set('productoId', $producto->id)->set('cantidad', 1)->set('precioUnitario', 22)
            ->set('cliente', 'Cliente '.$vez)->set('tipoComprobante', 'boleta')
            ->call('guardar')->assertHasNoErrors();
    }

    expect(DB::table('comprobantes')->orderBy('numero')->pluck('numero')->all())->toBe([1, 2, 3]);
});

test('una factura sin RUC no se emite', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    Livewire::actingAs($usuario)
        ->test(VentaFormulario::class)
        ->set('productoId', $producto->id)
        ->set('cantidad', 1)
        ->set('precioUnitario', 22)
        ->set('tipoComprobante', 'factura')
        ->set('clienteDocumento', '')
        ->call('guardar')
        ->assertSee('necesita el RUC');

    // La venta si quedo registrada: el stock ya salio del almacen.
    $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $producto->id, 'tipo' => 'venta']);
    $this->assertDatabaseCount('comprobantes', 0);
});

test('una venta sin comprobante se puede emitir despues desde la lista', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    Livewire::actingAs($usuario)->test(VentaFormulario::class)
        ->set('productoId', $producto->id)->set('cantidad', 1)->set('precioUnitario', 22)
        ->set('cliente', 'Pedro Mamani')->set('tipoComprobante', 'ninguno')
        ->call('guardar')->assertHasNoErrors();

    $venta = MovimientoInventarioModel::query()->where('tipo', 'venta')->firstOrFail();

    Livewire::actingAs($usuario)->test(VentasIndex::class)
        ->assertSee('Emitir')
        ->call('emitirComprobante', $venta->id)
        ->assertSet('clienteNombre', 'Pedro Mamani')
        ->call('confirmarComprobante')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('comprobantes', ['movimiento_inventario_id' => $venta->id, 'total' => 22.00]);
});

test('emitir dos veces por la misma venta no numera un segundo documento', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    Livewire::actingAs($usuario)->test(VentaFormulario::class)
        ->set('productoId', $producto->id)->set('cantidad', 1)->set('precioUnitario', 22)
        ->set('tipoComprobante', 'boleta')->call('guardar');

    $venta = MovimientoInventarioModel::query()->where('tipo', 'venta')->firstOrFail();

    Livewire::actingAs($usuario)->test(VentasIndex::class)
        ->call('emitirComprobante', $venta->id)
        ->call('confirmarComprobante');

    $this->assertDatabaseCount('comprobantes', 1);
});

test('quien no vende no puede emitir ni abrir el PDF de un comprobante', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    Livewire::actingAs($usuario)->test(VentaFormulario::class)
        ->set('productoId', $producto->id)->set('cantidad', 1)->set('precioUnitario', 22)
        ->set('tipoComprobante', 'boleta')->call('guardar');

    $id = DB::table('comprobantes')->value('id');

    $this->actingAs(usuarioDeCaja('acopiador'))->get(route('comprobantes.pdf', $id))->assertForbidden();
});

test('la entrega de un pedido no se puede facturar dos veces desde el historial de ventas', function () {
    $usuario = usuarioDeCaja();
    $producto = productoDeCaja();

    $pedido = \App\Infrastructure\Pedidos\Models\PedidoModel::create([
        'cliente_nombre' => 'Ana Flores', 'cliente_telefono' => '71234567',
        'cliente_direccion' => 'Av. Circunvalación 120', 'fecha' => now()->format('Y-m-d'),
        'estado' => 'confirmado', 'repartidor_id' => $usuario->id, 'codigo' => 'ABC123',
    ]);
    $pedido->items()->create([
        'producto_id' => $producto->id, 'nombre_producto' => 'Queso fresco',
        'cantidad' => 2, 'precio_unitario' => 22,
    ]);

    app(\App\Application\Pedidos\UseCases\EntregarPedidoUseCase::class)->ejecutar(
        new \App\Application\Pedidos\DTOs\EntregarPedidoData(
            id: $pedido->id,
            metodoPago: 'efectivo',
            montoCobrado: 44,
            fechaEntrega: now()->format('Y-m-d'),
        ),
    );

    app(\App\Application\Comprobantes\UseCases\EmitirComprobanteUseCase::class)->ejecutar([
        'tipo' => 'boleta', 'fecha' => now()->format('Y-m-d'),
        'clienteNombre' => 'Ana Flores', 'clienteDocumento' => null,
        'total' => 44, 'pedidoId' => $pedido->id,
    ], $usuario->id);

    $venta = MovimientoInventarioModel::query()->where('tipo', 'venta')->firstOrFail();

    // La venta que dejo la entrega es el mismo cobro que el pedido: la
    // pantalla muestra su comprobante en lugar de ofrecer emitir otro.
    Livewire::actingAs($usuario)->test(VentasIndex::class)
        ->assertSee('B001-000001')
        ->assertDontSee('Emitir');

    // Y aunque se pida a la fuerza, devuelve el mismo documento.
    $segundo = app(\App\Application\Comprobantes\UseCases\EmitirComprobanteUseCase::class)->ejecutar([
        'tipo' => 'boleta', 'fecha' => now()->format('Y-m-d'),
        'clienteNombre' => 'Ana Flores', 'clienteDocumento' => null,
        'total' => 44, 'movimientoInventarioId' => $venta->id,
    ], $usuario->id);

    expect($segundo['numero_completo'])->toBe('B001-000001');
    $this->assertDatabaseCount('comprobantes', 1);
});
