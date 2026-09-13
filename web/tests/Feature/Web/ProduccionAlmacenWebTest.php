<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Produccion\Formulario;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioProduccion(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('produccion');

    return $user;
}

test('registrar una produccion conectada a un producto y sus insumos actualiza el almacen', function () {
    $queso = ProductoModel::factory()->create(['nombre' => 'Queso fresco']);
    $cuajo = ProductoModel::factory()->insumo()->create(['nombre' => 'Cuajo']);
    MovimientoInventarioModel::factory()->create(['producto_id' => $cuajo->id, 'tipo' => 'compra', 'cantidad' => 10]);

    Livewire::actingAs(usuarioProduccion())
        ->test(Formulario::class)
        ->set('fecha', '2026-09-13')
        ->set('litrosProcesados', 100)
        ->set('quesosProducidos', 12)
        ->set('productoId', $queso->id)
        ->set('insumos', [['productoId' => $cuajo->id, 'cantidad' => 3]])
        ->call('guardar')
        ->assertRedirect(route('produccion.index'));

    expect(app(\App\Domain\Inventario\Repositories\MovimientoInventarioRepository::class)->stockDe($queso->id))->toBe(12.0)
        ->and(app(\App\Domain\Inventario\Repositories\MovimientoInventarioRepository::class)->stockDe($cuajo->id))->toBe(7.0);

    $this->assertDatabaseHas('produccion', ['producto_id' => $queso->id, 'quesos_producidos' => 12]);
    $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $queso->id, 'tipo' => 'produccion']);
    $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $cuajo->id, 'tipo' => 'uso_produccion', 'cantidad' => 3.00]);
});

test('si falta stock de un insumo, no queda registrada ni la produccion ni ningun movimiento', function () {
    $cuajo = ProductoModel::factory()->insumo()->create(['nombre' => 'Cuajo', 'unidad' => 'litro']);
    // Sin ninguna entrada previa: 0 en existencia.

    Livewire::actingAs(usuarioProduccion())
        ->test(Formulario::class)
        ->set('fecha', '2026-09-13')
        ->set('litrosProcesados', 100)
        ->set('quesosProducidos', 12)
        ->set('insumos', [['productoId' => $cuajo->id, 'cantidad' => 3]])
        ->call('guardar')
        ->assertSee('Solo hay 0 litro de Cuajo en existencia.');

    expect(ProduccionModel::count())->toBe(0)
        ->and(MovimientoInventarioModel::count())->toBe(0);
});
