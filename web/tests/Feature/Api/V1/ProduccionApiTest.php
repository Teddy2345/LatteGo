<?php

declare(strict_types=1);

use App\Infrastructure\Calidad\Models\CalidadModel;
use App\Infrastructure\Produccion\Models\DespachoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarUsuarioProduccion(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Sanctum::actingAs($user);

    return $user;
}

test('rechaza el acceso sin autenticacion', function () {
    $this->getJson('/api/v1/produccion')->assertUnauthorized();
});

test('lista producciones', function () {
    autenticarUsuarioProduccion();
    ProduccionModel::factory()->count(3)->create();

    $this->getJson('/api/v1/produccion')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('registra una produccion y calcula el rendimiento', function () {
    autenticarUsuarioProduccion();

    $respuesta = $this->postJson('/api/v1/produccion', [
        'fecha' => '2024-01-04',
        'litros_procesados' => 1000,
        'quesos_producidos' => 115,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.rendimiento_porcentaje', 11.5)
        ->assertJsonPath('data.rendimiento_en_rango_esperado', true);
});

test('marca fuera de rango sin bloquear el registro', function () {
    autenticarUsuarioProduccion();

    $respuesta = $this->postJson('/api/v1/produccion', [
        'fecha' => '2024-01-04',
        'litros_procesados' => 1000,
        'quesos_producidos' => 80,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.rendimiento_en_rango_esperado', false);
});

test('registra un despacho y calcula la merma', function () {
    autenticarUsuarioProduccion();
    $produccion = ProduccionModel::factory()->create(['quesos_producidos' => 120]);

    $respuesta = $this->postJson('/api/v1/despachos', [
        'produccion_id' => $produccion->id,
        'quesos_recibidos' => 110,
        'quesos_despachados' => 108,
    ]);

    $respuesta->assertCreated()->assertJsonPath('data.merma', 10);
});

test('lista despachos de una produccion', function () {
    autenticarUsuarioProduccion();
    $produccion = ProduccionModel::factory()->create();
    DespachoModel::factory()->count(2)->create(['produccion_id' => $produccion->id]);

    $this->getJson("/api/v1/despachos?produccion_id={$produccion->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('clasifica apto para pasteurizada sin historial de calidad', function () {
    autenticarUsuarioProduccion();
    $proveedor = ProveedorModel::factory()->create();

    $this->getJson("/api/v1/produccion/aptitud/{$proveedor->id}")
        ->assertOk()
        ->assertJsonPath('data.apto', true);
});

test('clasifica no apto si el ultimo analisis fue rechazado', function () {
    autenticarUsuarioProduccion();
    $proveedor = ProveedorModel::factory()->create();
    CalidadModel::factory()->create([
        'proveedor_id' => $proveedor->id,
        'fecha' => now()->format('Y-m-d'),
        'agua_agregada' => 3.0,
        'resultado' => 'rechazada',
        'motivo_rechazo' => 'adulteracion',
    ]);

    $this->getJson("/api/v1/produccion/aptitud/{$proveedor->id}")
        ->assertOk()
        ->assertJsonPath('data.apto', false);
});
