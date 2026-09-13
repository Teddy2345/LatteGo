<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarUsuarioApi(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Sanctum::actingAs($user);

    return $user;
}

test('rechaza el acceso sin autenticacion', function () {
    $this->getJson('/api/v1/acopios')->assertUnauthorized();
});

test('lista acopios', function () {
    autenticarUsuarioApi();
    AcopioModel::factory()->count(3)->create();

    $this->getJson('/api/v1/acopios')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('filtra acopios por proveedor', function () {
    autenticarUsuarioApi();
    $proveedor = ProveedorModel::factory()->create();
    AcopioModel::factory()->count(2)->create(['proveedor_id' => $proveedor->id]);
    AcopioModel::factory()->count(5)->create();

    $this->getJson("/api/v1/acopios?proveedor_id={$proveedor->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('filtra acopios pendientes de sincronizar', function () {
    autenticarUsuarioApi();
    AcopioModel::factory()->pendiente()->count(2)->create();
    AcopioModel::factory()->sincronizado()->count(3)->create();

    $this->getJson('/api/v1/acopios?pendientes=1')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('registra un acopio pendiente de sincronizar', function () {
    $user = autenticarUsuarioApi();
    $proveedor = ProveedorModel::factory()->create();

    $respuesta = $this->postJson('/api/v1/acopios', [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 30.5,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.estado', 'pendiente_sincronizar')
        ->assertJsonPath('data.proveedor_id', $proveedor->id)
        ->assertJsonPath('data.semana_pago.inicio', '2024-01-04')
        ->assertJsonPath('data.semana_pago.fin', '2024-01-10');

    $this->assertDatabaseHas('acopios', [
        'proveedor_id' => $proveedor->id,
        'acopiador_id' => $user->id,
    ]);
});

test('rechaza una cantidad de litros invalida', function () {
    autenticarUsuarioApi();
    $proveedor = ProveedorModel::factory()->create();

    $this->postJson('/api/v1/acopios', [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 0,
    ])->assertUnprocessable();
});

test('rechaza una perdida sin motivo', function () {
    autenticarUsuarioApi();
    $proveedor = ProveedorModel::factory()->create();

    $this->postJson('/api/v1/acopios', [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 20,
        'perdida_litros' => 2.5,
    ])->assertUnprocessable();
});

test('muestra un acopio', function () {
    autenticarUsuarioApi();
    $acopio = AcopioModel::factory()->create();

    $this->getJson("/api/v1/acopios/{$acopio->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $acopio->id);
});

test('sincroniza un acopio pendiente', function () {
    autenticarUsuarioApi();
    $acopio = AcopioModel::factory()->pendiente()->create();

    $this->patchJson("/api/v1/acopios/{$acopio->id}/sincronizar")
        ->assertOk()
        ->assertJsonPath('data.estado', 'sincronizado');

    $this->assertDatabaseHas('acopios', ['id' => $acopio->id, 'estado' => 'sincronizado']);
});

test('devuelve 404 al sincronizar un acopio inexistente', function () {
    autenticarUsuarioApi();

    $this->patchJson('/api/v1/acopios/999999/sincronizar')->assertNotFound();
});
