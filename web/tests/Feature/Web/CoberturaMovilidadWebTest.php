<?php

declare(strict_types=1);

use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use App\Presentation\Support\MapaReferencial;
use App\Presentation\Web\Livewire\Acopio\Index;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

function usuarioCobertura(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

/**
 * @return array{achacachi: MovilidadModel, motocarga: MovilidadModel}
 */
function flotaConRutas(?User $titularAchacachi = null): array
{
    $achacachi = RutaModel::create(['nombre' => 'Achacachi', 'paradas_referencia' => ['Achacachi, La Paz, Bolivia']]);
    $puertoPerez = RutaModel::create(['nombre' => 'Puerto Perez', 'paradas_referencia' => ['Batallas, La Paz, Bolivia', 'Puerto Pérez, La Paz, Bolivia']]);

    ProveedorModel::factory()->create(['nombre' => 'Juan Mamani', 'ruta_id' => $achacachi->id, 'activo' => true, 'telefono' => '71234567']);
    ProveedorModel::factory()->create(['nombre' => 'Rosa Inactiva', 'ruta_id' => $achacachi->id, 'activo' => false]);
    ProveedorModel::factory()->create(['nombre' => 'Pedro Condori', 'ruta_id' => $puertoPerez->id, 'activo' => true]);

    return [
        'achacachi' => MovilidadModel::create([
            'nombre' => 'Camión 02', 'tipo' => 'camion', 'ruta_id' => $achacachi->id, 'activa' => true,
            'usuario_id' => $titularAchacachi?->id,
        ]),
        'motocarga' => MovilidadModel::create(['nombre' => 'Motocarga', 'tipo' => 'motocarga', 'ruta_id' => $puertoPerez->id, 'activa' => true]),
    ];
}

test('quien ve reportes tiene las tarjetas con imagen de todos los camiones y la motocarga', function () {
    flotaConRutas();

    Livewire::actingAs(usuarioCobertura('admin'))
        ->test(Index::class)
        ->assertOk()
        ->assertSee('Camiones y motocarga')
        ->assertSee('Camión 02')
        ->assertSee('Motocarga')
        ->assertSee('img/movilidades/camion.svg', false)
        ->assertSee('img/movilidades/motocarga.svg', false);
});

test('al elegir una movilidad se ven solo los proveedores activos de su ruta y su mapa', function () {
    $flota = flotaConRutas();

    Livewire::actingAs(usuarioCobertura('admin'))
        ->test(Index::class)
        ->call('verCobertura', $flota['achacachi']->id)
        ->assertSee('Proveedores de la ruta')
        ->assertSee('Juan Mamani')
        ->assertSee('71234567')
        ->assertDontSee('Rosa Inactiva')
        ->assertDontSee('Pedro Condori')
        ->assertSee(e(MapaReferencial::embed(['Achacachi, La Paz, Bolivia'])), false)
        ->assertSee('Abrir en Google Maps')
        ->call('cerrarCobertura')
        ->assertDontSee('Proveedores de la ruta');
});

test('el acopiador solo ve su propia movilidad y no puede abrir la de otro', function () {
    $acopiador = usuarioCobertura('acopiador');
    $flota = flotaConRutas($acopiador);

    Livewire::actingAs($acopiador)
        ->test(Index::class)
        ->assertSee('Tu movilidad')
        ->assertSee('Camión 02')
        ->assertDontSee('img/movilidades/motocarga.svg', false)
        ->call('verCobertura', $flota['achacachi']->id)
        ->assertSee('Juan Mamani')
        ->call('verCobertura', $flota['motocarga']->id)
        ->assertForbidden();
});

test('no se puede cambiar desde el navegador la movilidad abierta para ver proveedores ajenos', function () {
    $acopiador = usuarioCobertura('acopiador');
    $flota = flotaConRutas($acopiador);

    expect(fn () => Livewire::actingAs($acopiador)
        ->test(Index::class)
        ->set('coberturaMovilidadId', $flota['motocarga']->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('el mapa de referencia sale de la planta y pasa por las paradas en orden', function () {
    config(['ecolactea.planta.ubicacion' => 'Santiago de Huata, La Paz, Bolivia']);

    expect(MapaReferencial::embed(['Batallas, Bolivia', 'Puerto Pérez, Bolivia']))
        ->toBe('https://maps.google.com/maps?saddr=Santiago+de+Huata%2C+La+Paz%2C+Bolivia&daddr=Batallas%2C+Bolivia+to%3APuerto+P%C3%A9rez%2C+Bolivia&output=embed')
        ->and(MapaReferencial::enlace(['Batallas, Bolivia', 'Puerto Pérez, Bolivia']))
        ->toBe('https://www.google.com/maps/dir/?api=1&origin=Santiago+de+Huata%2C+La+Paz%2C+Bolivia&destination=Puerto+P%C3%A9rez%2C+Bolivia&waypoints=Batallas%2C+Bolivia&travelmode=driving')
        // Sin paradas (entrega directa en planta) se muestra la planta.
        ->and(MapaReferencial::embed([]))
        ->toBe('https://maps.google.com/maps?q=Santiago+de+Huata%2C+La+Paz%2C+Bolivia&z=14&output=embed');
});
