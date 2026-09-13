<?php

declare(strict_types=1);

use App\Models\User;
use App\Presentation\Api\V1\Support\VinculacionDosFactores;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Crypt;

function adminCon2fa(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->forceFill([
        'two_factor_secret' => Crypt::encrypt('Z7CXX7MRKTGYBS77XIKCH74JP3ED6MCU'),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

/** Entra a la app como lo haria el telefono y devuelve el token emitido. */
function sesionMovil(User $user, string $dispositivo = 'Pixel de prueba'): string
{
    return $user->createToken($dispositivo)->plainTextToken;
}

test('un telefono sin vincular no obtiene el codigo aunque su sesion sea valida', function () {
    // Es la propiedad que sostiene el diseño: si bastara la sesion movil
    // (que solo pide contraseña), el 2FA no protegeria nada.
    $user = adminCon2fa();
    $token = sesionMovil($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/mobile/2fa/codigo')
        ->assertForbidden();
});

test('el telefono vinculado con el codigo de la web si recibe el codigo', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    $codigoVinculacion = VinculacionDosFactores::generarCodigo($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => $codigoVinculacion])
        ->assertCreated();

    $respuesta = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/mobile/2fa/codigo')
        ->assertOk();

    expect($respuesta->json('data.codigo'))->toMatch('/^\d{6}$/')
        ->and($respuesta->json('data.segundos_restantes'))->toBeGreaterThan(0);
});

test('un codigo de vinculacion no sirve dos veces', function () {
    $user = adminCon2fa();
    $codigoVinculacion = VinculacionDosFactores::generarCodigo($user);

    $primero = sesionMovil($user, 'Telefono del dueño');
    $this->withHeader('Authorization', "Bearer {$primero}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => $codigoVinculacion])
        ->assertCreated();

    // Un segundo telefono que alcanzo a ver el codigo ya no puede usarlo.
    $segundo = sesionMovil($user, 'Telefono ajeno');
    $this->app['auth']->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$segundo}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => $codigoVinculacion])
        ->assertStatus(422);

    $this->app['auth']->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$segundo}")
        ->getJson('/api/v1/mobile/2fa/codigo')
        ->assertForbidden();
});

test('vincular con un codigo inventado no sirve', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    VinculacionDosFactores::generarCodigo($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => 'XXX-YYY'])
        ->assertStatus(422);
});

test('la vinculacion de un usuario no le sirve a otro', function () {
    $duenio = adminCon2fa();
    $codigoVinculacion = VinculacionDosFactores::generarCodigo($duenio);

    $otro = adminCon2fa();
    $tokenOtro = sesionMovil($otro);

    $this->withHeader('Authorization', "Bearer {$tokenOtro}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => $codigoVinculacion])
        ->assertStatus(422);
});

test('desvincular deja al telefono sin acceso al codigo', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    $codigoVinculacion = VinculacionDosFactores::generarCodigo($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => $codigoVinculacion])
        ->assertCreated();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/mobile/2fa/vincular')
        ->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/mobile/2fa/codigo')
        ->assertForbidden();
});

test('el estado le dice a la app si ese telefono esta vinculado', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/mobile/2fa/estado')
        ->assertOk()
        ->assertJsonPath('data.vinculado', false)
        ->assertJsonPath('data.requiere_2fa', true)
        ->assertJsonPath('data.dos_factores_activo', true);
});

test('un acopiador sin 2fa no puede vincular ni pedir codigos', function () {
    app(RolePermissionSeeder::class)->run();
    $acopiador = User::factory()->create();
    $acopiador->assignRole('acopiador');
    $token = sesionMovil($acopiador);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/mobile/2fa/vincular', ['codigo' => 'ABC-DEF'])
        ->assertStatus(422);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/mobile/2fa/codigo')
        ->assertForbidden();
});

test('sin sesion movil no se puede pedir el codigo', function () {
    $this->getJson('/api/v1/mobile/2fa/codigo')->assertUnauthorized();
});
