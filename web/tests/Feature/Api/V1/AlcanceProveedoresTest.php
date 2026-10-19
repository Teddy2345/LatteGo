<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Cada camion, la motocarga y la recepcion en planta atienden solo a los
 * proveedores de su ruta, desde la web, la API o el telefono.
 */
function acopiadorDelCamion(string $ruta): array
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('acopiador');
    $rutaModel = RutaModel::create(['nombre' => $ruta]);
    MovilidadModel::create(['nombre' => "Camión de {$ruta}", 'tipo' => 'camion', 'ruta_id' => $rutaModel->id, 'activa' => true, 'usuario_id' => $user->id]);

    return [$user, $rutaModel];
}

function acopioDelTelefono(int $proveedorId, string $fecha): array
{
    return [
        'request_id' => (string) Str::uuid(), 'proveedor_id' => $proveedorId, 'fecha' => $fecha, 'cantidad_litros' => 30,
        'latitud' => -15.84, 'longitud' => -69.92, 'precision_m' => 8, 'capturado_en' => $fecha.' 07:00:00',
    ];
}

test('el camion no ve, ni abre, ni registra, ni traslada a proveedores de otra ruta', function () {
    [$acopiador, $achacachi] = acopiadorDelCamion('Achacachi');
    $copacabana = RutaModel::create(['nombre' => 'Copacabana']);
    $propio = ProveedorModel::factory()->create(['nombre' => 'Juan Mamani', 'ruta_id' => $achacachi->id, 'activo' => true]);
    $ajeno = ProveedorModel::factory()->create(['nombre' => 'Rosa Quispe', 'ruta_id' => $copacabana->id, 'activo' => true]);
    Sanctum::actingAs($acopiador, ['*']);

    $this->getJson('/api/v1/mobile/catalogo')
        ->assertOk()
        ->assertJsonFragment(['nombre' => 'Juan Mamani'])
        ->assertJsonMissing(['nombre' => 'Rosa Quispe']);

    $this->getJson("/api/v1/mobile/proveedores/{$propio->id}")->assertOk();
    $this->getJson("/api/v1/mobile/proveedores/{$ajeno->id}")
        ->assertForbidden()
        ->assertJsonPath('message', 'Ese proveedor no pertenece a tu ruta.');

    $this->postJson('/api/v1/mobile/acopios', acopioDelTelefono($ajeno->id, now()->format('Y-m-d')))->assertForbidden();
    $this->postJson('/api/v1/acopios', ['proveedor_id' => $ajeno->id, 'fecha' => now()->format('Y-m-d'), 'cantidad_litros' => 20])->assertForbidden();
    // El traslado ya no lo pide el acopiador: ese endpoint es del proveedor.
    $this->postJson('/api/v1/mobile/proveedor/cambio-zona', ['ruta_solicitada_id' => $achacachi->id, 'fecha_cambio' => now()->format('Y-m-d')])
        ->assertForbidden();

    $this->postJson('/api/v1/mobile/acopios', acopioDelTelefono($propio->id, now()->format('Y-m-d')))->assertCreated();

    expect(AcopioModel::query()->where('proveedor_id', $ajeno->id)->exists())->toBeFalse();
});

test('un acopiador sin movilidad asignada no recibe proveedores y la app puede avisarle', function () {
    app(RolePermissionSeeder::class)->run();
    $sinCamion = User::factory()->create();
    $sinCamion->assignRole('acopiador');
    ProveedorModel::factory()->create(['ruta_id' => RutaModel::create(['nombre' => 'Batallas'])->id, 'activo' => true]);
    Sanctum::actingAs($sinCamion, ['*']);

    $this->getJson('/api/v1/mobile/catalogo')
        ->assertOk()
        ->assertJsonPath('sin_movilidad', null)
        ->assertJsonPath('data.sin_movilidad', true)
        ->assertJsonCount(0, 'data.proveedores');
});

test('un acopio tomado antes de un traslado aprobado se acepta al llegar con señal', function () {
    [$acopiador, $achacachi] = acopiadorDelCamion('Achacachi');
    $copacabana = RutaModel::create(['nombre' => 'Copacabana']);
    // El proveedor ya se paso a Copacabana, aprobado hoy; el acopio es de ayer, cuando aun era de Achacachi.
    $trasladado = ProveedorModel::factory()->create(['ruta_id' => $copacabana->id, 'activo' => true]);
    SolicitudCambioZonaModel::create([
        'proveedor_id' => $trasladado->id, 'ruta_actual_id' => $achacachi->id, 'ruta_solicitada_id' => $copacabana->id,
        'fecha_cambio' => now()->format('Y-m-d'), 'estado' => 'aprobada', 'solicitado_por' => $acopiador->id,
        'revisado_por' => $acopiador->id, 'fecha_revision' => now(),
    ]);
    Sanctum::actingAs($acopiador, ['*']);

    $this->postJson('/api/v1/mobile/acopios', acopioDelTelefono($trasladado->id, now()->subDay()->format('Y-m-d')))->assertCreated();

    // Pero no sirve para seguir acopiandolo despues del traslado.
    $this->postJson('/api/v1/mobile/acopios', acopioDelTelefono($trasladado->id, now()->addDay()->format('Y-m-d')))->assertForbidden();
});
