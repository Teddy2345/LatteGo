<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Pagos\Models\PagoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarUsuarioPago(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Sanctum::actingAs($user);

    return $user;
}

test('rechaza el acceso sin autenticacion', function () {
    $this->getJson('/api/v1/pagos')->assertUnauthorized();
});

test('lista pagos', function () {
    autenticarUsuarioPago();
    PagoModel::factory()->count(3)->create();

    $this->getJson('/api/v1/pagos')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('genera la planilla de pago para una semana con acopios sincronizados', function () {
    autenticarUsuarioPago();
    $proveedor = ProveedorModel::factory()->create(['precio_litro' => 3.5]);
    AcopioModel::factory()->sincronizado()->create([
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 100,
    ]);
    AcopioModel::factory()->sincronizado()->create([
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-09',
        'cantidad_litros' => 50,
    ]);

    $respuesta = $this->postJson('/api/v1/pagos/generar-planilla', ['fecha_referencia' => '2024-01-04']);

    $respuesta->assertOk()->assertJsonCount(1, 'data');
    $respuesta->assertJsonPath('data.0.total_litros', 150);
    $respuesta->assertJsonPath('data.0.total_pagar', 525);

    $this->assertDatabaseHas('pagos', [
        'proveedor_id' => $proveedor->id,
        'semana_inicio' => '2024-01-04',
        'total_pagar' => 525.0,
    ]);
});

test('no duplica la planilla si se genera dos veces', function () {
    autenticarUsuarioPago();
    $proveedor = ProveedorModel::factory()->create();
    AcopioModel::factory()->sincronizado()->create([
        'proveedor_id' => $proveedor->id,
        'fecha' => '2024-01-04',
        'cantidad_litros' => 80,
    ]);

    $this->postJson('/api/v1/pagos/generar-planilla', ['fecha_referencia' => '2024-01-04'])
        ->assertJsonCount(1, 'data');

    $this->postJson('/api/v1/pagos/generar-planilla', ['fecha_referencia' => '2024-01-04'])
        ->assertJsonCount(0, 'data');
});

test('muestra un pago', function () {
    autenticarUsuarioPago();
    $pago = PagoModel::factory()->create();

    $this->getJson("/api/v1/pagos/{$pago->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $pago->id);
});

test('marca un pago como pagado', function () {
    autenticarUsuarioPago();
    $pago = PagoModel::factory()->create(['estado' => 'pendiente', 'fecha_pago' => null]);

    $this->patchJson("/api/v1/pagos/{$pago->id}/pagar")
        ->assertOk()
        ->assertJsonPath('data.estado', 'pagado');

    $this->assertDatabaseHas('pagos', ['id' => $pago->id, 'estado' => 'pagado']);
});

test('devuelve 404 al mostrar un pago inexistente', function () {
    autenticarUsuarioPago();

    $this->getJson('/api/v1/pagos/999999')->assertNotFound();
});
