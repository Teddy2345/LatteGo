<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Inventario\CatalogoFormulario;
use App\Presentation\Web\Livewire\Tienda\Catalogo as TiendaCatalogo;
use App\Presentation\Web\Livewire\Ventas\Formulario as VentaFormulario;
use App\Presentation\Web\Livewire\Ventas\Index as VentasIndex;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function usuarioVentas(string $rol = 'ventas'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

/** Un queso con los tres precios y existencia suficiente para venderlo. */
function quesoConTresPrecios(float $publico = 18.0, ?float $vecino = 15.0, ?float $proveedor = 13.5): ProductoModel
{
    $queso = ProductoModel::factory()->create([
        'nombre' => 'Queso fresco', 'unidad' => 'pieza',
        'precio_referencia' => $publico, 'precio_vecino' => $vecino, 'precio_proveedor' => $proveedor,
    ]);

    MovimientoInventarioModel::factory()->create([
        'producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => 50,
    ]);

    return $queso;
}

test('la venta propone el precio que le toca a cada tipo de comprador', function (string $tipo, float $esperado) {
    $queso = quesoConTresPrecios();

    Livewire::actingAs(usuarioVentas())
        ->test(VentaFormulario::class)
        ->set('productoId', $queso->id)
        ->set('tipoCliente', $tipo)
        ->assertSet('precioUnitario', $esperado);
})->with([
    'de fuera del distrito' => ['externo', 18.0],
    'vecino de Huata' => ['vecino', 15.0],
    'proveedor de la planta' => ['proveedor', 13.5],
]);

test('un producto sin precio propio se le cobra igual a todos', function () {
    $queso = quesoConTresPrecios(publico: 20.0, vecino: null, proveedor: null);

    Livewire::actingAs(usuarioVentas())
        ->test(VentaFormulario::class)
        ->set('productoId', $queso->id)
        ->set('tipoCliente', 'vecino')
        ->assertSet('precioUnitario', 20.0)
        ->set('tipoCliente', 'proveedor')
        ->assertSet('precioUnitario', 20.0);
});

test('la venta guarda a que tipo de cliente se le cobro y se ve en el historial', function () {
    $queso = quesoConTresPrecios();

    Livewire::actingAs(usuarioVentas())
        ->test(VentaFormulario::class)
        ->set('tipoCliente', 'vecino')
        ->set('productoId', $queso->id)
        ->set('cantidad', 2)
        ->set('cliente', 'Rosa Mamani')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('ventas.index'));

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $queso->id, 'tipo' => 'venta',
        'tipo_cliente' => 'vecino', 'precio_unitario' => 15.0, 'total' => 30.0,
    ]);

    Livewire::actingAs(usuarioVentas())
        ->test(VentasIndex::class)
        ->assertSee('Rosa Mamani')
        ->assertSee('Vecino');
});

test('quien atiende puede ajustar el precio y el tipo de cliente queda igual registrado', function () {
    $queso = quesoConTresPrecios();

    Livewire::actingAs(usuarioVentas())
        ->test(VentaFormulario::class)
        ->set('tipoCliente', 'proveedor')
        ->set('productoId', $queso->id)
        ->set('cantidad', 1)
        // Trato distinto por una pieza con la corteza dañada.
        ->set('precioUnitario', 10)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $queso->id, 'tipo_cliente' => 'proveedor', 'precio_unitario' => 10.0,
    ]);
});

test('la tienda web sigue mostrando el precio publico aunque haya precios mas bajos', function () {
    quesoConTresPrecios(publico: 18.0, vecino: 15.0, proveedor: 13.5);

    Livewire::test(TiendaCatalogo::class)
        ->assertSee('18.00')
        ->assertDontSee('15.00')
        ->assertDontSee('13.50');
});

test('el catalogo guarda los tres precios y deja vacios los que no aplican', function () {
    Livewire::actingAs(usuarioVentas('almacen'))
        ->test(CatalogoFormulario::class)
        ->set('tipo', 'producto_terminado')
        ->set('nombre', 'Yogurt de frutilla')
        ->set('unidad', 'litro')
        ->set('precioReferencia', 12)
        ->set('precioVecino', 9.5)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('productos', [
        'nombre' => 'Yogurt de frutilla', 'precio_referencia' => 12.0,
        'precio_vecino' => 9.5, 'precio_proveedor' => null,
    ]);
});

test('un tipo de cliente inventado desde el navegador no se guarda', function () {
    $queso = quesoConTresPrecios();

    Livewire::actingAs(usuarioVentas())
        ->test(VentaFormulario::class)
        ->set('productoId', $queso->id)
        ->set('cantidad', 1)
        ->set('tipoCliente', 'socio-fundador')
        ->call('guardar')
        ->assertHasErrors(['tipoCliente']);

    expect(DB::table('movimientos_inventario')->where('tipo', 'venta')->count())->toBe(0);
});

test('las ventas anteriores a los precios diferenciados se leen como publicas', function () {
    $queso = quesoConTresPrecios();

    MovimientoInventarioModel::factory()->create([
        'producto_id' => $queso->id, 'tipo' => 'venta', 'cantidad' => 1,
        'precio_unitario' => 18, 'cliente' => 'Venta antigua', 'tipo_cliente' => null,
    ]);

    Livewire::actingAs(usuarioVentas())
        ->test(VentasIndex::class)
        ->assertSee('Venta antigua')
        ->assertSee('Externo');
});
