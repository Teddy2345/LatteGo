<?php

declare(strict_types=1);

use App\Application\Produccion\UseCases\GestionarRecetasUseCase;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Produccion\Recetas;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function prepararReceta(): array
{
    app(RolePermissionSeeder::class)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole('produccion');
    $queso = ProductoModel::factory()->create(['nombre' => 'Queso de prueba']);
    $insumo = ProductoModel::factory()->insumo()->create(['nombre' => 'Sal de prueba', 'unidad' => 'kg']);
    $id = app(GestionarRecetasUseCase::class)->crear(['nombre' => 'Queso base 100 L', 'productoId' => $queso->id,
        'litrosBase' => 100, 'descripcion' => 'Agregar sal.', 'insumos' => [['productoId' => $insumo->id, 'cantidad' => 2]]], $usuario->id);
    return [$usuario, $queso, $insumo, $id];
}

test('la receta rechaza insumos duplicados cantidades invalidas y productos terminados como insumos', function () {
    [$usuario, $queso, $sal] = prepararReceta();
    Livewire::actingAs($usuario)->test(Recetas::class)->set('nombre', 'Nueva')
        ->set('productoId', $queso->id)->set('insumos', [['productoId' => $sal->id, 'cantidad' => 1], ['productoId' => $sal->id, 'cantidad' => 2]])
        ->call('guardar')->assertHasErrors(['insumos.0.productoId'])
        ->set('insumos', [['productoId' => $queso->id, 'cantidad' => 1]])->call('guardar')->assertHasErrors(['insumos.0.productoId'])
        ->set('insumos', [['productoId' => $sal->id, 'cantidad' => -1]])->call('guardar')->assertHasErrors(['insumos.0.cantidad']);
    expect(DB::table('recetas')->count())->toBe(1);
});

test('acopiador no accede a recetas y supervisor no puede crearlas', function () {
    [$usuario] = prepararReceta();
    $usuario->syncRoles('acopiador');
    Livewire::actingAs($usuario)->test(Recetas::class)->assertForbidden();
    $usuario->syncRoles('supervisor');
    Livewire::actingAs($usuario)->test(Recetas::class)->assertOk()->call('guardar')->assertForbidden();
});

test('admin puede crear una receta sin modificar stock', function () {
    [$usuario, $queso, $sal] = prepararReceta();
    $usuario->syncRoles('admin');
    Livewire::actingAs($usuario)->test(Recetas::class)->set('nombre', 'Receta versión 2')
        ->set('productoId', $queso->id)->set('insumos', [['productoId' => $sal->id, 'cantidad' => 1.25]])
        ->call('guardar')->assertHasNoErrors()->assertSee('Receta guardada');
    $this->assertDatabaseHas('recetas', ['nombre' => 'Receta versión 2']);
    expect(MovimientoInventarioModel::count())->toBe(0);
});
