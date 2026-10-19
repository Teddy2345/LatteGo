<?php

declare(strict_types=1);

use App\Application\Pedidos\DTOs\CrearPedidoData;
use App\Application\Pedidos\DTOs\ItemCarritoData;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Domain\Inventario\Entities\Producto;
use App\Domain\Pedidos\Exceptions\ItemPedidoInvalidoException;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Presentation\Web\Livewire\Tienda\Carrito;
use App\Presentation\Web\Livewire\Tienda\Catalogo;
use App\Presentation\Web\Livewire\Tienda\Checkout;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function productoTienda(string $nombre, string $unidad, float $stock = 50): ProductoModel
{
    $producto = ProductoModel::factory()->create([
        'tipo' => 'producto_terminado', 'nombre' => $nombre, 'unidad' => $unidad, 'precio_referencia' => 10, 'activo' => true,
    ]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => $stock]);

    return $producto;
}

test('las unidades que se entregan enteras se reconocen como se escriban', function () {
    expect(Producto::unidadEsEntera('pieza'))->toBeTrue()
        ->and(Producto::unidadEsEntera(' Piezas '))->toBeTrue()
        ->and(Producto::unidadEsEntera('sobres'))->toBeTrue()
        ->and(Producto::unidadEsEntera('unidades'))->toBeTrue()
        ->and(Producto::unidadEsEntera('litro'))->toBeFalse()
        ->and(Producto::unidadEsEntera('kilo'))->toBeFalse();
});

test('en el carrito un queso por pieza se redondea a piezas enteras y el yogurt por litro no', function () {
    $queso = productoTienda('Queso fresco', 'pieza');
    $yogurt = productoTienda('Yogurt natural', 'litro');
    session(['carrito' => [$queso->id => 1.0, $yogurt->id => 1.0]]);

    Livewire::test(Carrito::class)
        ->call('actualizar', $queso->id, '2.4')
        ->call('actualizar', $yogurt->id, '1,5');

    expect(session('carrito'))->toBe([$queso->id => 2.0, $yogurt->id => 1.5]);

    Livewire::test(Carrito::class)->call('actualizar', $queso->id, '0.3');

    expect(session('carrito')[$queso->id])->toBe(1.0);
});

test('desde el catalogo tampoco entra media pieza', function () {
    $queso = productoTienda('Queso madurado', 'pieza');

    Livewire::test(Catalogo::class)
        ->set("cantidades.{$queso->id}", 1.5)
        ->call('agregar', $queso->id);

    expect(session('carrito'))->toBe([$queso->id => 2.0]);
});

test('el pedido rechaza una fraccion de un producto por pieza aunque llegue sin pasar por el carrito', function () {
    $queso = productoTienda('Queso fresco', 'pieza');

    expect(fn () => app(CrearPedidoUseCase::class)->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle 1',
        fecha: '2026-09-14',
        observaciones: null,
        items: [new ItemCarritoData(productoId: $queso->id, cantidad: 0.5)],
    )))->toThrow(ItemPedidoInvalidoException::class, 'Queso fresco se vende por pieza: indica una cantidad sin decimales.');
});

test('un mismo visitante no puede mandar pedidos sin parar', function () {
    $yogurt = productoTienda('Yogurt natural', 'litro', stock: 500);
    RateLimiter::clear('tienda-pedidos:127.0.0.1');

    for ($i = 1; $i <= 5; $i++) {
        session(['carrito' => [$yogurt->id => 1.0]]);
        Livewire::test(Checkout::class)
            ->set('nombre', "Cliente {$i}")->set('telefono', '71234567')->set('direccion', 'Calle 1')
            ->call('confirmar')
            ->assertRedirect();
    }

    session(['carrito' => [$yogurt->id => 1.0]]);
    Livewire::test(Checkout::class)
        ->set('nombre', 'Cliente 6')->set('telefono', '71234567')->set('direccion', 'Calle 1')
        ->call('confirmar')
        ->assertNoRedirect()
        ->assertSee('Ya enviaste varios pedidos seguidos.');

    $this->assertDatabaseMissing('pedidos', ['cliente_nombre' => 'Cliente 6']);
});
