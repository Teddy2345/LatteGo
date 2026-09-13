<?php

declare(strict_types=1);

use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Acopio\Formulario;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function acopiadorDeRuta(RutaModel $ruta): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('acopiador');

    MovilidadModel::create([
        'nombre' => 'Camión de prueba', 'tipo' => 'camion',
        'ruta_id' => $ruta->id, 'activa' => true, 'usuario_id' => $user->id,
    ]);

    return $user;
}

test('el acopio manual guarda la zona del proveedor y no queda sin ruta', function () {
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);
    $acopiador = acopiadorDeRuta($ruta);

    Livewire::actingAs($acopiador)
        ->test(Formulario::class)
        ->set('proveedorId', $proveedor->id)
        ->set('cantidadLitros', 30.0)
        ->call('guardar')
        ->assertRedirect(route('acopios.index'));

    // Sin la ruta, el consolidado diario agrupa el acopio en "Sin zona asignada".
    $this->assertDatabaseHas('acopios', [
        'proveedor_id' => $proveedor->id,
        'acopiador_id' => $acopiador->id,
        'ruta_id' => $ruta->id,
        'cantidad_litros' => 30.00,
    ]);
});

test('el acopiador solo puede elegir a los proveedores de su propia ruta', function () {
    $suRuta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $otraRuta = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    ProveedorModel::factory()->create(['nombre' => 'Juan De Su Ruta', 'ruta_id' => $suRuta->id, 'activo' => true]);
    ProveedorModel::factory()->create(['nombre' => 'Ana De Otra Ruta', 'ruta_id' => $otraRuta->id, 'activo' => true]);

    Livewire::actingAs(acopiadorDeRuta($suRuta))
        ->test(Formulario::class)
        ->assertSee('Juan De Su Ruta')
        ->assertDontSee('Ana De Otra Ruta');
});

test('el acopiador no puede registrar un acopio de un proveedor de otra ruta', function () {
    $suRuta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $otraRuta = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    $ajeno = ProveedorModel::factory()->create(['ruta_id' => $otraRuta->id, 'activo' => true]);

    Livewire::actingAs(acopiadorDeRuta($suRuta))
        ->test(Formulario::class)
        ->set('proveedorId', $ajeno->id)
        ->set('cantidadLitros', 30.0)
        ->call('guardar')
        ->assertSet('error', 'Ese proveedor no pertenece a tu ruta.');

    expect(\App\Infrastructure\Acopio\Models\AcopioModel::query()->count())->toBe(0);
});

test('quien ve reportes de toda la planta puede acopiar en cualquier ruta', function () {
    app(RolePermissionSeeder::class)->run();
    $ruta = RutaModel::create(['nombre' => 'Copacabana', 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Pedro Condori', 'ruta_id' => $ruta->id, 'activo' => true]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::actingAs($admin)
        ->test(Formulario::class)
        ->assertSee('Pedro Condori')
        ->set('proveedorId', $proveedor->id)
        ->set('cantidadLitros', 12.0)
        ->call('guardar')
        ->assertRedirect(route('acopios.index'));

    $this->assertDatabaseHas('acopios', ['proveedor_id' => $proveedor->id, 'ruta_id' => $ruta->id]);
});
