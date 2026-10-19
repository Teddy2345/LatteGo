<?php

declare(strict_types=1);

use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalidoException;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Pedidos\Cobros;
use App\Presentation\Web\Livewire\Pedidos\Detalle as PedidoDetalle;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function usuarioCobros(string $rol = 'ventas'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

/** Un pedido confirmado y con repartidor, listo para registrar la entrega. */
function pedidoListoParaEntregar(User $repartidor, float $total = 30.0): PedidoModel
{
    // Nombre unico en el catalogo: cada pedido de la prueba trae el suyo.
    $producto = ProductoModel::factory()->create(['unidad' => 'pieza', 'precio_referencia' => $total]);
    MovimientoInventarioModel::factory()->create([
        'producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => 20,
    ]);

    $pedido = PedidoModel::query()->create([
        'cliente_nombre' => 'Rosa Mamani', 'cliente_telefono' => '71234567',
        'cliente_direccion' => 'Calle 1', 'fecha' => now()->format('Y-m-d'),
        'estado' => 'confirmado', 'repartidor_id' => $repartidor->id,
    ]);

    $pedido->items()->create([
        'producto_id' => $producto->id, 'nombre_producto' => $producto->nombre,
        'cantidad' => 1, 'precio_unitario' => $total,
    ]);

    return $pedido->fresh('items');
}

test('un cobro por Yape exige el numero de operacion', function () {
    $repartidor = usuarioCobros('repartidor');
    $pedido = pedidoListoParaEntregar($repartidor);

    Livewire::actingAs($repartidor)
        ->test(PedidoDetalle::class, ['pedido' => $pedido->id])
        ->set('metodoPago', 'yape')
        ->set('montoCobrado', 30)
        ->set('fechaEntrega', now()->format('Y-m-d'))
        ->call('entregar')
        ->assertHasErrors(['referenciaPago']);

    expect($pedido->fresh()->estado)->toBe('confirmado');
});

test('con el numero de operacion la entrega queda registrada y cotejable', function () {
    $repartidor = usuarioCobros('repartidor');
    $pedido = pedidoListoParaEntregar($repartidor);

    Livewire::actingAs($repartidor)
        ->test(PedidoDetalle::class, ['pedido' => $pedido->id])
        ->set('metodoPago', 'yape')
        ->set('montoCobrado', 30)
        ->set('fechaEntrega', now()->format('Y-m-d'))
        ->set('referenciaPago', ' 01234567 ')
        ->call('entregar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pedidos', [
        'id' => $pedido->id, 'estado' => 'entregado',
        'metodo_pago' => 'yape', 'referencia_pago' => '01234567',
    ]);
});

test('el efectivo no pide comprobante y no guarda ninguno', function () {
    $repartidor = usuarioCobros('repartidor');
    $pedido = pedidoListoParaEntregar($repartidor);

    Livewire::actingAs($repartidor)
        ->test(PedidoDetalle::class, ['pedido' => $pedido->id])
        ->set('metodoPago', 'efectivo')
        ->set('montoCobrado', 30)
        ->set('fechaEntrega', now()->format('Y-m-d'))
        // Aunque llegue una referencia, el efectivo no la conserva.
        ->set('referenciaPago', 'NO-CORRESPONDE')
        ->call('entregar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('pedidos', [
        'id' => $pedido->id, 'metodo_pago' => 'efectivo', 'referencia_pago' => null,
    ]);
});

test('la regla vive en el dominio, no solo en el formulario', function () {
    $repartidor = usuarioCobros('repartidor');
    $pedido = pedidoListoParaEntregar($repartidor);

    $entidad = app(\App\Domain\Pedidos\Repositories\PedidoRepository::class)->buscarPorId($pedido->id);

    expect(fn () => $entidad->entregar(
        \App\Domain\Pedidos\ValueObjects\MetodoPago::Yape,
        30.0,
        new DateTimeImmutable('today'),
        '   ',
    ))->toThrow(EstadoPedidoInvalidoException::class, 'Anota el número de operación del pago por Yape.');
});

test('la pantalla de cobros junta los digitales y deja fuera el efectivo', function () {
    $repartidor = usuarioCobros('repartidor');

    foreach ([['yape', '111', 30.0], ['transferencia', '222', 45.0], ['efectivo', null, 20.0]] as [$metodo, $referencia, $monto]) {
        $pedido = pedidoListoParaEntregar($repartidor, $monto);
        $pedido->update([
            'estado' => 'entregado', 'metodo_pago' => $metodo,
            'referencia_pago' => $referencia, 'monto_cobrado' => $monto,
            'fecha_entrega' => now()->format('Y-m-d'),
        ]);
    }

    Livewire::actingAs(usuarioCobros('ventas'))
        ->test(Cobros::class)
        ->assertOk()
        ->assertSee('111')
        ->assertSee('222')
        // El efectivo se cuadra con la caja, no con el banco.
        ->assertSee('75.00')
        ->assertDontSee('20.00');
});

test('la pantalla de cobros avisa de las entregas sin numero de operacion', function () {
    $repartidor = usuarioCobros('repartidor');
    $pedido = pedidoListoParaEntregar($repartidor);
    $pedido->update([
        'estado' => 'entregado', 'metodo_pago' => 'yape', 'referencia_pago' => null,
        'monto_cobrado' => 30, 'fecha_entrega' => now()->format('Y-m-d'),
    ]);

    Livewire::actingAs(usuarioCobros('ventas'))
        ->test(Cobros::class)
        ->assertSee('Sin registrar')
        ->assertSee('Son entregas registradas antes de pedirlo');
});

test('quien no ve pedidos tampoco ve los cobros', function () {
    Livewire::actingAs(usuarioCobros('calidad'))
        ->test(Cobros::class)
        ->assertForbidden();
});

test('el administrador sube el QR de cobro y los telefonos lo reciben', function () {
    Storage::fake('public');

    $admin = usuarioCobros('admin');

    Livewire::actingAs($admin)
        ->test(\App\Presentation\Web\Livewire\Pedidos\Cobros::class)
        ->set('qrCobro', UploadedFile::fake()->image('yape.png'))
        ->call('guardarQr')
        ->assertHasNoErrors()
        ->assertSee('QR actualizado');

    Storage::disk('public')->assertExists(config('ecolactea.cobros.qr_path'));
});

test('quien solo reparte no cambia el QR de la planta', function () {
    Livewire::actingAs(usuarioCobros('repartidor'))
        ->test(\App\Presentation\Web\Livewire\Pedidos\Cobros::class)
        ->set('qrCobro', UploadedFile::fake()->image('otro.png'))
        ->call('guardarQr')
        ->assertForbidden();
});
