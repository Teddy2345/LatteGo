<?php

declare(strict_types=1);

use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function acopiadorConRuta(string $ruta = 'Achacachi'): array
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('acopiador');
    $rutaModel = RutaModel::create(['nombre' => $ruta, 'paradas_referencia' => [$ruta.', La Paz, Bolivia']]);
    MovilidadModel::create([
        'nombre' => 'Camión 02', 'tipo' => 'camion', 'ruta_id' => $rutaModel->id, 'activa' => true, 'usuario_id' => $user->id,
    ]);

    return [$user, $rutaModel];
}

test('el acopiador ve su ruta con el mapa y los proveedores que atiende', function () {
    [$acopiador, $ruta] = acopiadorConRuta();
    ProveedorModel::factory()->create(['nombre' => 'Juan Mamani', 'ruta_id' => $ruta->id, 'activo' => true, 'telefono' => '71234567', 'finca' => 'Predio Titicaca']);
    ProveedorModel::factory()->create(['nombre' => 'Rosa Inactiva', 'ruta_id' => $ruta->id, 'activo' => false]);
    ProveedorModel::factory()->create(['nombre' => 'De otra ruta', 'ruta_id' => RutaModel::create(['nombre' => 'Copacabana'])->id, 'activo' => true]);
    Sanctum::actingAs($acopiador, ['*']);

    $respuesta = $this->getJson('/api/v1/mobile/mi-ruta')
        ->assertOk()
        ->assertJsonPath('data.movilidad', 'Camión 02')
        ->assertJsonPath('data.zona', 'Achacachi')
        ->assertJsonPath('data.paradas.0', 'Achacachi')
        ->assertJsonCount(1, 'data.proveedores')
        ->assertJsonPath('data.proveedores.0.nombre', 'Juan Mamani')
        ->assertJsonPath('data.proveedores.0.telefono', '71234567')
        ->assertJsonPath('data.proveedores.0.finca', 'Predio Titicaca')
        ->assertJsonMissing(['nombre' => 'De otra ruta'])
        ->assertJsonMissing(['nombre' => 'Rosa Inactiva']);

    // El mapa es el mismo recorrido de referencia que muestra la web.
    expect($respuesta->json('data.mapa'))->toContain('maps.google.com', 'output=embed', 'Achacachi')
        ->and($respuesta->json('data.mapa_enlace'))->toContain('google.com/maps/dir/');
});

test('un acopiador sin movilidad asignada recibe el aviso en lugar de una ruta', function () {
    app(RolePermissionSeeder::class)->run();
    $sinCamion = User::factory()->create();
    $sinCamion->assignRole('acopiador');
    Sanctum::actingAs($sinCamion, ['*']);

    $this->getJson('/api/v1/mobile/mi-ruta')
        ->assertNotFound()
        ->assertJsonPath('message', 'Todavía no tienes una movilidad asignada. Pídele a un administrador que te asigne la tuya.');
});

test('quien no acopia no puede ver la ruta', function () {
    app(RolePermissionSeeder::class)->run();
    $ventas = User::factory()->create();
    $ventas->assignRole('ventas');
    Sanctum::actingAs($ventas, ['*']);

    $this->getJson('/api/v1/mobile/mi-ruta')->assertForbidden();
});

test('la pagina del mapa se sirve con un enlace firmado para el WebView de la app', function () {
    // Google solo entrega su mapa dentro de un iframe servido por un sitio, y
    // el WebView no lleva la sesion de la app: por eso el enlace va firmado.
    [$acopiador] = acopiadorConRuta();
    Sanctum::actingAs($acopiador, ['*']);

    $pagina = $this->getJson('/api/v1/mobile/mi-ruta')->assertOk()->json('data.mapa_pagina');

    expect($pagina)->toContain('/mapa-ruta/', 'signature=');

    $this->get($pagina)
        ->assertOk()
        ->assertSee('<iframe', false)
        ->assertSee('maps.google.com', false)
        ->assertSee('Achacachi', false);
});

test('un enlace de mapa manipulado o sin firma no abre nada', function () {
    [$acopiador] = acopiadorConRuta();
    Sanctum::actingAs($acopiador, ['*']);
    $pagina = $this->getJson('/api/v1/mobile/mi-ruta')->assertOk()->json('data.mapa_pagina');

    $this->get(explode('?', $pagina)[0])->assertForbidden();
    $this->get(str_replace('signature=', 'signature=0', $pagina))->assertForbidden();
});
