<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Inventario\EntradaFormulario;
use App\Presentation\Web\Livewire\Produccion\Costeo;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioConRol(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('registrar una entrada con costo unitario lo guarda en el movimiento', function () {
    $insumo = ProductoModel::factory()->insumo()->create();

    Livewire::actingAs(usuarioConRol('almacen'))
        ->test(EntradaFormulario::class)
        ->set('productoId', $insumo->id)
        ->set('fecha', '2026-09-12')
        ->set('cantidad', 10)
        ->set('costoUnitario', 4.5)
        ->call('guardar')
        ->assertRedirect(route('inventario.index'));

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $insumo->id,
        'tipo' => 'compra',
        'precio_unitario' => 4.50,
    ]);
});

test('el costeo de una produccion combina el precio ponderado de la leche y el costo de sus insumos', function () {
    $proveedor = ProveedorModel::factory()->create(['precio_litro' => 3.0, 'activo' => true]);
    AcopioModel::factory()->sincronizado()->create([
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-10', // jueves: inicio de la semana de acopio
        'cantidad_litros' => 200,
    ]);

    $cuajo = ProductoModel::factory()->insumo()->create(['nombre' => 'Cuajo']);
    MovimientoInventarioModel::factory()->create([
        'producto_id' => $cuajo->id,
        'tipo' => 'compra',
        'cantidad' => 10,
        'precio_unitario' => 5.0,
    ]);

    $produccion = ProduccionModel::factory()->create([
        'fecha' => '2026-09-12', // dentro de la misma semana jueves-miercoles
        'litros_procesados' => 100,
        'quesos_producidos' => 10,
    ]);
    MovimientoInventarioModel::factory()->create([
        'producto_id' => $cuajo->id,
        'produccion_id' => $produccion->id,
        'tipo' => 'uso_produccion',
        'cantidad' => 3,
    ]);

    Livewire::actingAs(usuarioConRol('produccion'))
        ->test(Costeo::class, ['produccion' => $produccion->id])
        ->assertSee('S/ 300.00') // 100 L * S/ 3.00/L de leche
        ->assertSee('S/ 15.00') // 3 * 5.00 de cuajo
        ->assertSee('S/ 315.00') // costo total
        ->assertSee('S/ 31.50'); // costo por queso (315 / 10)
});

test('acopiador no puede ver el costeo de produccion', function () {
    $produccion = ProduccionModel::factory()->create();

    Livewire::actingAs(usuarioConRol('acopiador'))
        ->test(Costeo::class, ['produccion' => $produccion->id])
        ->assertForbidden();
});
