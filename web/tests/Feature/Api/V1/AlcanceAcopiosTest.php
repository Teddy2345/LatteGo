<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Acopio\Index;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

function usuarioAlcance(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

/**
 * @return array{yo: User, mio: AcopioModel, ajeno: AcopioModel}
 */
function acopiosDeDosAcopiadores(): array
{
    $yo = usuarioAlcance('acopiador');
    $companero = usuarioAlcance('acopiador');

    $mio = AcopioModel::factory()->pendiente()->create([
        'acopiador_id' => $yo->id,
        'proveedor_id' => ProveedorModel::factory()->create(['nombre' => 'Proveedora Propia'])->id,
    ]);
    $ajeno = AcopioModel::factory()->pendiente()->create([
        'acopiador_id' => $companero->id,
        'proveedor_id' => ProveedorModel::factory()->create(['nombre' => 'Proveedor De Otra Ruta'])->id,
    ]);

    return ['yo' => $yo, 'mio' => $mio, 'ajeno' => $ajeno];
}

test('en la api el acopiador solo lista y abre sus propios acopios', function () {
    ['yo' => $yo, 'mio' => $mio, 'ajeno' => $ajeno] = acopiosDeDosAcopiadores();
    Sanctum::actingAs($yo, ['*']);

    $this->getJson('/api/v1/acopios')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mio->id);

    $this->getJson("/api/v1/acopios/{$mio->id}")->assertOk();
    $this->getJson("/api/v1/acopios/{$ajeno->id}")->assertForbidden();
    $this->patchJson("/api/v1/acopios/{$ajeno->id}/sincronizar")->assertForbidden();

    expect($ajeno->fresh()->estado)->toBe('pendiente_sincronizar');
});

test('desde el telefono no se puede abrir el acopio de otro acopiador ni su punto gps', function () {
    ['yo' => $yo, 'mio' => $mio, 'ajeno' => $ajeno] = acopiosDeDosAcopiadores();
    Sanctum::actingAs($yo, ['*']);

    $this->getJson("/api/v1/mobile/acopios/{$mio->id}")->assertOk();
    $this->getJson("/api/v1/mobile/acopios/{$ajeno->id}")
        ->assertForbidden()
        ->assertJsonMissing(['latitud' => $ajeno->latitud]);
});

test('quien ve reportes sigue viendo los acopios de toda la planta', function () {
    ['ajeno' => $ajeno] = acopiosDeDosAcopiadores();
    Sanctum::actingAs(usuarioAlcance('supervisor'), ['*']);

    $this->getJson('/api/v1/acopios')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson("/api/v1/acopios/{$ajeno->id}")->assertOk();
    $this->getJson("/api/v1/mobile/acopios/{$ajeno->id}")->assertOk();
});

test('en la web el acopiador ve y sincroniza solo sus acopios', function () {
    ['yo' => $yo, 'mio' => $mio, 'ajeno' => $ajeno] = acopiosDeDosAcopiadores();

    Livewire::actingAs($yo)
        ->test(Index::class)
        ->assertSee('Proveedora Propia')
        ->assertDontSee('Proveedor De Otra Ruta')
        ->call('sincronizar', $mio->id)
        ->call('sincronizar', $ajeno->id)
        ->assertForbidden();

    expect($mio->fresh()->estado)->toBe('sincronizado')
        ->and($ajeno->fresh()->estado)->toBe('pendiente_sincronizar');
});

test('no se registra un acopio ni un analisis con fecha futura', function () {
    // Admin atiende a cualquier ruta: aqui solo importa la fecha.
    Sanctum::actingAs(usuarioAlcance('admin'), ['*']);
    $proveedor = ProveedorModel::factory()->create();
    $movil = [
        'proveedor_id' => $proveedor->id, 'cantidad_litros' => 20, 'latitud' => -15.84, 'longitud' => -69.92,
        'precision_m' => 8, 'capturado_en' => now()->toDateTimeString(),
    ];

    $this->postJson('/api/v1/acopios', ['proveedor_id' => $proveedor->id, 'fecha' => now()->addDays(3)->format('Y-m-d'), 'cantidad_litros' => 20])
        ->assertStatus(422)
        ->assertJsonPath('errors.fecha.0', 'La fecha no puede ser futura.');

    // Desde el telefono se tolera un dia por la diferencia de zona horaria, no mas.
    $this->postJson('/api/v1/mobile/acopios', $movil + ['request_id' => (string) Str::uuid(), 'fecha' => now()->addDay()->format('Y-m-d')])
        ->assertCreated();
    $this->postJson('/api/v1/mobile/acopios', $movil + ['request_id' => (string) Str::uuid(), 'fecha' => now()->addDays(2)->format('Y-m-d')])
        ->assertStatus(422);
});
