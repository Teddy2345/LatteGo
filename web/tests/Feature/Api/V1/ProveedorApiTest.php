<?php

declare(strict_types=1);

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function actuarComoUsuarioAutenticado(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Sanctum::actingAs($user);

    return $user;
}

test('rechaza el acceso sin autenticacion', function () {
    $this->getJson('/api/v1/proveedores')->assertUnauthorized();
});

test('lista proveedores', function () {
    actuarComoUsuarioAutenticado();
    ProveedorModel::factory()->count(3)->create();

    $this->getJson('/api/v1/proveedores')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('lista solo proveedores activos cuando se pide', function () {
    actuarComoUsuarioAutenticado();
    ProveedorModel::factory()->create(['activo' => true]);
    ProveedorModel::factory()->create(['activo' => false]);

    $this->getJson('/api/v1/proveedores?solo_activos=1')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('crea un proveedor', function () {
    actuarComoUsuarioAutenticado();
    $ruta = RutaModel::create(['nombre' => 'Ruta Test']);

    $respuesta = $this->postJson('/api/v1/proveedores', [
        'nombre' => 'Carlos Mamani',
        'cedula' => '112233',
        'telefono' => '77712345',
        'finca' => 'Finca Test',
        'litros_prom' => 45,
        'precio_litro' => 3.6,
        'ruta_id' => $ruta->id,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.cedula', '112233')
        ->assertJsonPath('data.activo', true);

    $this->assertDatabaseHas('proveedores', ['cedula' => '112233']);
});

test('rechaza crear un proveedor con datos invalidos', function () {
    actuarComoUsuarioAutenticado();

    $this->postJson('/api/v1/proveedores', [
        'nombre' => '',
        'cedula' => '123',
        'litros_prom' => -5,
        'precio_litro' => 0,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['nombre', 'cedula', 'litros_prom', 'precio_litro']);
});

test('rechaza crear un proveedor con cedula duplicada', function () {
    actuarComoUsuarioAutenticado();
    ProveedorModel::factory()->create(['cedula' => '999888']);

    $this->postJson('/api/v1/proveedores', [
        'nombre' => 'Otro Nombre',
        'cedula' => '999888',
        'litros_prom' => 10,
        'precio_litro' => 3.0,
    ])->assertUnprocessable();
});

test('muestra un proveedor', function () {
    actuarComoUsuarioAutenticado();
    $proveedor = ProveedorModel::factory()->create();

    $this->getJson("/api/v1/proveedores/{$proveedor->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $proveedor->id);
});

test('devuelve 404 al mostrar un proveedor inexistente', function () {
    actuarComoUsuarioAutenticado();

    $this->getJson('/api/v1/proveedores/999999')->assertNotFound();
});

test('actualiza un proveedor sin tocar la cedula', function () {
    actuarComoUsuarioAutenticado();
    $proveedor = ProveedorModel::factory()->create(['cedula' => '654321']);

    $this->putJson("/api/v1/proveedores/{$proveedor->id}", [
        'nombre' => 'Nombre Actualizado',
        'litros_prom' => 99,
        'precio_litro' => 4.1,
    ])->assertOk()
        ->assertJsonPath('data.nombre', 'Nombre Actualizado')
        ->assertJsonPath('data.cedula', '654321');
});

test('reasigna la ruta de un proveedor', function () {
    actuarComoUsuarioAutenticado();
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => null]);
    $ruta = RutaModel::create(['nombre' => 'Nueva Ruta']);

    $this->patchJson("/api/v1/proveedores/{$proveedor->id}/ruta", ['ruta_id' => $ruta->id])
        ->assertOk()
        ->assertJsonPath('data.ruta_id', $ruta->id);
});

test('cambia el estado de un proveedor', function () {
    actuarComoUsuarioAutenticado();
    $proveedor = ProveedorModel::factory()->create(['activo' => true]);

    $this->patchJson("/api/v1/proveedores/{$proveedor->id}/estado", ['activo' => false])
        ->assertOk()
        ->assertJsonPath('data.activo', false);
});

test('elimina un proveedor', function () {
    actuarComoUsuarioAutenticado();
    $proveedor = ProveedorModel::factory()->create();

    $this->deleteJson("/api/v1/proveedores/{$proveedor->id}")->assertNoContent();

    $this->assertDatabaseMissing('proveedores', ['id' => $proveedor->id]);
});
