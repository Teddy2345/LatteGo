<?php

declare(strict_types=1);

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarConRol(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    Sanctum::actingAs($user);

    return $user;
}

test('un acopiador no puede eliminar un proveedor', function () {
    autenticarConRol('acopiador');
    $proveedor = ProveedorModel::factory()->create();

    $this->deleteJson("/api/v1/proveedores/{$proveedor->id}")->assertForbidden();
});

test('un acopiador si puede registrar un acopio', function () {
    autenticarConRol('acopiador');
    $proveedor = ProveedorModel::factory()->create();

    $this->postJson('/api/v1/acopios', [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 20,
    ])->assertCreated();
});

test('un tecnico de calidad no puede generar la planilla de pago', function () {
    autenticarConRol('calidad');

    $this->postJson('/api/v1/pagos/generar-planilla', ['fecha_referencia' => '2024-01-04'])
        ->assertForbidden();
});

test('un operador de produccion no puede registrar un acopio', function () {
    autenticarConRol('produccion');
    $proveedor = ProveedorModel::factory()->create();

    $this->postJson('/api/v1/acopios', [
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 20,
    ])->assertForbidden();
});

test('un supervisor puede editar proveedores pero no eliminarlos', function () {
    autenticarConRol('supervisor');
    $proveedor = ProveedorModel::factory()->create(['cedula' => '556677']);

    $this->putJson("/api/v1/proveedores/{$proveedor->id}", [
        'nombre' => 'Nombre Actualizado',
        'litros_prom' => 40,
        'precio_litro' => 3.5,
    ])->assertOk();

    $this->deleteJson("/api/v1/proveedores/{$proveedor->id}")->assertForbidden();
});

test('un acopiador no puede listar analisis de calidad', function () {
    autenticarConRol('acopiador');

    $this->getJson('/api/v1/calidad')->assertForbidden();
});
