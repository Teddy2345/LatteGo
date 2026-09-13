<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Calidad\Models\CalidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarUsuarioCalidad(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');

    Sanctum::actingAs($user);

    return $user;
}

test('rechaza el acceso sin autenticacion', function () {
    $this->getJson('/api/v1/calidad')->assertUnauthorized();
});

test('lista analisis de calidad', function () {
    autenticarUsuarioCalidad();
    CalidadModel::factory()->count(3)->create();

    $this->getJson('/api/v1/calidad')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('filtra analisis por proveedor', function () {
    autenticarUsuarioCalidad();
    $proveedor = ProveedorModel::factory()->create();
    CalidadModel::factory()->count(2)->create(['proveedor_id' => $proveedor->id]);
    CalidadModel::factory()->count(4)->create();

    $this->getJson("/api/v1/calidad?proveedor_id={$proveedor->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('acepta un analisis sin agua añadida y alcohol aceptado', function () {
    autenticarUsuarioCalidad();
    $proveedor = ProveedorModel::factory()->create();
    $acopio = AcopioModel::factory()->create(['proveedor_id' => $proveedor->id]);

    $respuesta = $this->postJson('/api/v1/calidad', [
        'proveedor_id' => $proveedor->id,
        'acopio_id' => $acopio->id,
        'fecha' => '2024-01-04',
        'temperatura' => 4.0,
        'grasa' => 3.5,
        'solidos_no_grasos' => 8.5,
        'densidad' => 30.5,
        'proteina' => 3.2,
        'lactosa' => 4.6,
        'sales' => 0.7,
        'agua_agregada' => 0,
        'ph' => 6.7,
        'prueba_alcohol_aceptada' => true,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.resultado', 'aceptada')
        ->assertJsonPath('data.sancion_aplicada', 'ninguna');
});

test('rechaza automaticamente por adulteracion cuando hay agua añadida', function () {
    autenticarUsuarioCalidad();
    // El proveedor arranca activo de forma explicita: la fabrica lo deja
    // activo solo el 90% de las veces y la prueba comprueba, justamente, que
    // la primera adulteracion no lo retira.
    $proveedor = ProveedorModel::factory()->create(['activo' => true]);
    $acopio = AcopioModel::factory()->create(['proveedor_id' => $proveedor->id]);

    $respuesta = $this->postJson('/api/v1/calidad', [
        'proveedor_id' => $proveedor->id,
        'acopio_id' => $acopio->id,
        'fecha' => '2024-01-04',
        'temperatura' => 4.0,
        'grasa' => 3.5,
        'solidos_no_grasos' => 8.5,
        'densidad' => 22.0,
        'proteina' => 3.2,
        'lactosa' => 4.6,
        'sales' => 0.7,
        'agua_agregada' => 3.5,
        'ph' => 6.7,
        'prueba_alcohol_aceptada' => true,
    ]);

    $respuesta->assertCreated()
        ->assertJsonPath('data.resultado', 'rechazada')
        ->assertJsonPath('data.motivo_rechazo', 'adulteracion')
        ->assertJsonPath('data.sancion_aplicada', 'descuento_semana');

    $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'activo' => true]);
});

test('la segunda adulteracion retira temporalmente al proveedor', function () {
    autenticarUsuarioCalidad();
    $proveedor = ProveedorModel::factory()->create(['activo' => true]);

    $payload = fn (int $acopioId) => [
        'proveedor_id' => $proveedor->id,
        'acopio_id' => $acopioId,
        'fecha' => '2024-01-04',
        'temperatura' => 4.0,
        'grasa' => 3.5,
        'solidos_no_grasos' => 8.5,
        'densidad' => 22.0,
        'proteina' => 3.2,
        'lactosa' => 4.6,
        'sales' => 0.7,
        'agua_agregada' => 3.5,
        'ph' => 6.7,
        'prueba_alcohol_aceptada' => true,
    ];

    $acopio1 = AcopioModel::factory()->create(['proveedor_id' => $proveedor->id]);
    $this->postJson('/api/v1/calidad', $payload($acopio1->id))->assertCreated();

    $acopio2 = AcopioModel::factory()->create(['proveedor_id' => $proveedor->id]);
    $respuesta = $this->postJson('/api/v1/calidad', $payload($acopio2->id));

    $respuesta->assertCreated()->assertJsonPath('data.sancion_aplicada', 'retiro_temporal');

    $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'activo' => false]);
});

test('muestra un analisis de calidad', function () {
    autenticarUsuarioCalidad();
    $calidad = CalidadModel::factory()->create();

    $this->getJson("/api/v1/calidad/{$calidad->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $calidad->id);
});

test('devuelve 404 al mostrar un analisis inexistente', function () {
    autenticarUsuarioCalidad();

    $this->getJson('/api/v1/calidad/999999')->assertNotFound();
});
