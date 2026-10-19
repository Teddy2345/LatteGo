<?php

declare(strict_types=1);

use App\Application\Produccion\UseCases\GestionarRecetasUseCase;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Produccion\LoteFormulario;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioProduccion(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('produccion');

    return $user;
}

/** Lo que hace falta para un lote: el producto, su insumo y una receta. */
function quesoConReceta(User $usuario, float $stockInsumo = 10): array
{
    $queso = ProductoModel::factory()->create(['nombre' => 'Queso fresco', 'unidad' => 'pieza']);
    $cuajo = ProductoModel::factory()->insumo()->create(['nombre' => 'Cuajo', 'unidad' => 'litro']);

    if ($stockInsumo > 0) {
        MovimientoInventarioModel::factory()->create([
            'producto_id' => $cuajo->id, 'tipo' => 'compra', 'cantidad' => $stockInsumo,
        ]);
    }

    $receta = app(GestionarRecetasUseCase::class)->crear([
        'nombre' => 'Queso fresco por pieza', 'productoId' => $queso->id,
        'cantidadBase' => 1, 'litrosBase' => 0, 'descripcion' => 'Cuajar y moldear.',
        'insumos' => [['productoId' => $cuajo->id, 'cantidad' => 0.25]],
    ], $usuario->id);

    return [$queso, $cuajo, $receta];
}

test('registrar un lote conectado a un producto y sus insumos actualiza el almacen', function () {
    $usuario = usuarioProduccion();
    [$queso, $cuajo, $receta] = quesoConReceta($usuario);

    Livewire::actingAs($usuario)
        ->test(LoteFormulario::class)
        ->set('productos.0.recetaId', $receta)
        ->set('productos.0.cantidadPlanificada', 12)
        ->call('aplicarRecetas')
        ->assertSet('productos.0.productoId', $queso->id)
        // 0,25 L de cuajo por pieza: 12 piezas piden 3 L.
        ->assertSet('insumos.0.cantidad', 3.0)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('produccion.index'));

    $existencias = app(MovimientoInventarioRepository::class);

    expect($existencias->stockDe($queso->id))->toBe(12.0)
        ->and($existencias->stockDe($cuajo->id))->toBe(7.0);

    $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $queso->id, 'tipo' => 'produccion']);
    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $cuajo->id, 'tipo' => 'uso_produccion', 'cantidad' => 3.00,
    ]);
    $this->assertDatabaseHas('produccion_productos', [
        'producto_id' => $queso->id, 'cantidad_planificada' => 12, 'cantidad_producida' => 12,
    ]);
});

test('si falta stock de un insumo, no queda registrada ni la produccion ni ningun movimiento', function () {
    $usuario = usuarioProduccion();
    // Sin ninguna entrada previa: 0 en existencia.
    [, , $receta] = quesoConReceta($usuario, stockInsumo: 0);

    Livewire::actingAs($usuario)
        ->test(LoteFormulario::class)
        ->set('productos.0.recetaId', $receta)
        ->set('productos.0.cantidadPlanificada', 12)
        ->call('aplicarRecetas')
        ->call('guardar')
        ->assertSee('Stock insuficiente de Cuajo');

    expect(ProduccionModel::count())->toBe(0)
        ->and(MovimientoInventarioModel::count())->toBe(0);
});

test('una misma tanda puede sacar dos productos y el insumo compartido se descuenta una vez', function () {
    $usuario = usuarioProduccion();
    [$queso, $cuajo, $recetaQueso] = quesoConReceta($usuario, stockInsumo: 20);

    // Del suero del mismo lote sale ricotta, que usa el mismo cuajo.
    $ricotta = ProductoModel::factory()->create(['nombre' => 'Ricotta', 'unidad' => 'kilo']);
    $recetaRicotta = app(GestionarRecetasUseCase::class)->crear([
        'nombre' => 'Ricotta del suero', 'productoId' => $ricotta->id,
        'cantidadBase' => 1, 'litrosBase' => 0, 'descripcion' => 'Del suero del queso.',
        'insumos' => [['productoId' => $cuajo->id, 'cantidad' => 0.1]],
    ], $usuario->id);

    Livewire::actingAs($usuario)
        ->test(LoteFormulario::class)
        ->set('productos.0.recetaId', $recetaQueso)
        ->set('productos.0.cantidadPlanificada', 10)
        ->call('agregarProducto')
        ->set('productos.1.recetaId', $recetaRicotta)
        ->set('productos.1.cantidadPlanificada', 4)
        ->call('aplicarRecetas')
        ->assertSet('productos.0.productoId', $queso->id)
        ->assertSet('productos.1.productoId', $ricotta->id)
        // 10 × 0,25 + 4 × 0,1 = 2,90 de cuajo, en una sola linea.
        ->assertCount('insumos', 1)
        ->assertSet('insumos.0.cantidad', 2.9)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('produccion.index'));

    $existencias = app(MovimientoInventarioRepository::class);

    expect($existencias->stockDe($queso->id))->toBe(10.0)
        ->and($existencias->stockDe($ricotta->id))->toBe(4.0)
        ->and($existencias->stockDe($cuajo->id))->toBe(17.1);

    $lote = ProduccionModel::firstOrFail();

    $this->assertDatabaseCount('produccion', 1);
    $this->assertDatabaseHas('produccion_productos', ['produccion_id' => $lote->id, 'producto_id' => $queso->id, 'cantidad_producida' => 10]);
    $this->assertDatabaseHas('produccion_productos', ['produccion_id' => $lote->id, 'producto_id' => $ricotta->id, 'cantidad_producida' => 4]);
    // Un solo consumo de cuajo para todo el lote.
    expect(MovimientoInventarioModel::query()->where('tipo', 'uso_produccion')->count())->toBe(1);

    Livewire::actingAs($usuario)
        ->test(\App\Presentation\Web\Livewire\Produccion\Index::class)
        ->assertSee('Queso fresco')
        ->assertSee('Ricotta');
});

test('el mismo producto no puede ir dos veces en el lote', function () {
    $usuario = usuarioProduccion();
    [, , $receta] = quesoConReceta($usuario, stockInsumo: 20);

    Livewire::actingAs($usuario)
        ->test(LoteFormulario::class)
        ->set('productos.0.recetaId', $receta)
        ->set('productos.0.cantidadPlanificada', 5)
        ->call('agregarProducto')
        ->set('productos.1.recetaId', $receta)
        ->set('productos.1.cantidadPlanificada', 3)
        ->call('aplicarRecetas')
        ->call('guardar')
        ->assertSee('no puede ir dos veces');

    expect(ProduccionModel::count())->toBe(0);
});

test('la ficha del lote sale en PDF con sus productos, su receta y lo consumido', function () {
    $usuario = usuarioProduccion();
    [$queso, , $receta] = quesoConReceta($usuario, stockInsumo: 20);

    Livewire::actingAs($usuario)
        ->test(LoteFormulario::class)
        ->set('productos.0.recetaId', $receta)
        ->set('productos.0.cantidadPlanificada', 8)
        ->call('aplicarRecetas')
        ->call('guardar')
        ->assertHasNoErrors();

    $lote = ProduccionModel::firstOrFail();

    $respuesta = $this->actingAs($usuario)->get(route('produccion.ficha', $lote->id));

    $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(strlen($respuesta->getContent()))->toBeGreaterThan(1000);

    // La pantalla ofrece la descarga.
    Livewire::actingAs($usuario)
        ->test(\App\Presentation\Web\Livewire\Produccion\Index::class)
        ->assertSee('Ficha PDF');
});
