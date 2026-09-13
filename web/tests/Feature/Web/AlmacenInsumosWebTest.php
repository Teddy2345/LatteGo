<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Inventario\CatalogoFormulario;
use App\Presentation\Web\Livewire\Inventario\EntradaFormulario;
use App\Presentation\Web\Livewire\Inventario\Index as AlmacenIndex;
use App\Presentation\Web\Livewire\Inventario\SalidaFormulario;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioAlmacen(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('el encargado de almacen da de alta un insumo nuevo en el catalogo', function () {
    Livewire::actingAs(usuarioAlmacen('almacen'))
        ->test(CatalogoFormulario::class)
        ->set('tipo', 'insumo')
        ->set('nombre', 'Cuajo líquido')
        ->set('categoria', 'Aditivos')
        ->set('unidad', 'litro')
        ->set('precioReferencia', 0)
        ->set('stockMinimo', 5)
        ->call('guardar')
        ->assertRedirect(route('inventario.index'));

    $this->assertDatabaseHas('productos', [
        'nombre' => 'Cuajo líquido',
        'tipo' => 'insumo',
        'categoria' => 'Aditivos',
        'stock_minimo' => 5.00,
    ]);
});

test('produccion no puede dar de alta insumos aunque vea el almacen', function () {
    Livewire::actingAs(usuarioAlmacen('produccion'))
        ->test(CatalogoFormulario::class)
        ->assertForbidden();
});

test('registrar una entrada de insumo aumenta el stock con su lote', function () {
    $insumo = ProductoModel::factory()->insumo()->create(['nombre' => 'Bolsas de empaque']);

    Livewire::actingAs(usuarioAlmacen('almacen'))
        ->test(EntradaFormulario::class)
        ->set('productoId', $insumo->id)
        ->set('fecha', '2026-09-12')
        ->set('cantidad', 50)
        ->set('lote', 'L-2026-09')
        ->set('motivo', 'Compra a Distribuidora Illimani')
        ->call('guardar')
        ->assertRedirect(route('inventario.index'));

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $insumo->id,
        'tipo' => 'compra',
        'cantidad' => 50.00,
        'lote' => 'L-2026-09',
        'observaciones' => 'Compra a Distribuidora Illimani',
    ]);
});

test('registrar una salida de insumo descuenta el stock', function () {
    $insumo = ProductoModel::factory()->insumo()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'compra', 'cantidad' => 50]);

    Livewire::actingAs(usuarioAlmacen('almacen'))
        ->test(SalidaFormulario::class)
        ->set('productoId', $insumo->id)
        ->set('fecha', '2026-09-12')
        ->set('cantidad', 10)
        ->set('motivo', 'Uso en Producción #125')
        ->call('guardar')
        ->assertRedirect(route('inventario.index'));

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $insumo->id,
        'tipo' => 'uso_produccion',
        'cantidad' => 10.00,
    ]);
});

test('no se puede sacar mas insumo del que hay en existencia', function () {
    $insumo = ProductoModel::factory()->insumo()->create(['nombre' => 'Sal fina', 'unidad' => 'kilo']);
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'compra', 'cantidad' => 5]);

    Livewire::actingAs(usuarioAlmacen('almacen'))
        ->test(SalidaFormulario::class)
        ->set('productoId', $insumo->id)
        ->set('fecha', '2026-09-12')
        ->set('cantidad', 20)
        ->call('guardar')
        ->assertSee('Solo hay 5 kilo de Sal fina en existencia.');

    expect(MovimientoInventarioModel::where('tipo', 'uso_produccion')->count())->toBe(0);
});

test('produccion y ventas ven el almacen pero no pueden registrar entradas ni salidas', function () {
    Livewire::actingAs(usuarioAlmacen('produccion'))->test(EntradaFormulario::class)->assertForbidden();
    Livewire::actingAs(usuarioAlmacen('ventas'))->test(SalidaFormulario::class)->assertForbidden();
});

test('el stock mezcla correctamente entradas y salidas de distinto tipo', function () {
    // Este es exactamente el caso que exponia el bug original: el calculo de
    // saldo solo miraba tipo = 'produccion' como entrada; una 'compra' se
    // restaba en vez de sumarse.
    $insumo = ProductoModel::factory()->insumo()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'compra', 'cantidad' => 100]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'uso_produccion', 'cantidad' => 30]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'compra', 'cantidad' => 20]);

    $stock = app(\App\Domain\Inventario\Repositories\MovimientoInventarioRepository::class)->stockDe($insumo->id);

    expect($stock)->toBe(90.0);
});

test('el almacen resalta los insumos con stock por debajo del minimo', function () {
    $insumo = ProductoModel::factory()->insumo()->create(['nombre' => 'Cultivo láctico', 'stock_minimo' => 10]);
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'compra', 'cantidad' => 4]);

    Livewire::actingAs(usuarioAlmacen('admin'))
        ->test(AlmacenIndex::class)
        ->assertSee('Cultivo láctico')
        ->assertSee('Por agotarse');
});
