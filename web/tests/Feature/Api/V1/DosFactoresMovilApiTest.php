<?php

declare(strict_types=1);

use App\Models\User;
use App\Presentation\Web\Livewire\Auth\TwoFactorSetup;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function adminCon2fa(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('admin');
    $user->forceFill([
        'two_factor_secret' => Crypt::encrypt('Z7CXX7MRKTGYBS77XIKCH74JP3ED6MCU'),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['RECUP-UNO-1111', 'RECUP-DOS-2222'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

/** Entra a la app como lo haria el telefono y devuelve el token emitido. */
function sesionMovil(User $user, string $dispositivo = 'Pixel de prueba'): string
{
    return $user->createToken($dispositivo)->plainTextToken;
}

/** El telefono genera el codigo de vinculacion en su campana. */
function codigoQueMuestraLaApp(string $token): string
{
    app('auth')->forgetGuards();

    return test()->withToken($token)
        ->postJson('/api/v1/mobile/2fa/codigo-vinculacion')
        ->assertCreated()
        ->json('data.codigo');
}

/** Su dueño lo escribe en la pantalla de 2FA de la web. */
function escribirEnLaWeb(User $user, string $codigo): \Livewire\Features\SupportTesting\Testable
{
    app('auth')->forgetGuards();
    $pantalla = Livewire::actingAs($user)->test(TwoFactorSetup::class)
        ->set('codigoTelefono', $codigo)
        ->call('vincularTelefono');
    app('auth')->forgetGuards();

    return $pantalla;
}

function pedirCodigoALaCampana(string $token): \Illuminate\Testing\TestResponse
{
    app('auth')->forgetGuards();

    return test()->withToken($token)->getJson('/api/v1/mobile/2fa/codigo');
}

test('un telefono sin vincular no obtiene el codigo aunque su sesion sea valida', function () {
    // Es la propiedad que sostiene el diseño: si bastara la sesion movil
    // (que solo pide contraseña), el 2FA no protegeria nada.
    $token = sesionMovil(adminCon2fa());

    pedirCodigoALaCampana($token)->assertForbidden();
});

test('el telefono genera el codigo, se escribe en la web y desde ahi recibe el codigo de 6 digitos', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);

    $codigo = codigoQueMuestraLaApp($token);
    expect($codigo)->toMatch('/^[A-Z0-9]{3}-[A-Z0-9]{3}$/');

    escribirEnLaWeb($user, $codigo)->assertSet('errorVinculacion', '')->assertSee('Teléfono vinculado');

    $respuesta = pedirCodigoALaCampana($token)->assertOk();

    expect($respuesta->json('data.codigo'))->toMatch('/^\d{6}$/')
        ->and($respuesta->json('data.segundos_restantes'))->toBeGreaterThan(0);

    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/mobile/2fa/estado')->assertJsonPath('data.vinculado', true);
});

test('el codigo se acepta escrito en minusculas o sin guion', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    $codigo = codigoQueMuestraLaApp($token);

    escribirEnLaWeb($user, strtolower(str_replace('-', ' ', $codigo)))->assertSet('errorVinculacion', '');

    pedirCodigoALaCampana($token)->assertOk();
});

test('un codigo de vinculacion no sirve dos veces', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    $codigo = codigoQueMuestraLaApp($token);

    escribirEnLaWeb($user, $codigo)->assertSet('errorVinculacion', '');
    escribirEnLaWeb($user, $codigo)->assertSet('errorVinculacion', 'El código no es válido o ya venció. Genera uno nuevo en la campana de la app.');
});

test('el codigo del telefono de otra cuenta no vincula nada', function () {
    // Alguien que ve el codigo en el celular ajeno no puede vincularlo a su
    // propia cuenta, ni el dueño del celular queda vinculado por eso.
    $duenio = adminCon2fa();
    $tokenDuenio = sesionMovil($duenio);
    $codigo = codigoQueMuestraLaApp($tokenDuenio);

    escribirEnLaWeb(adminCon2fa(), $codigo)->assertSet('errorVinculacion', 'El código no es válido o ya venció. Genera uno nuevo en la campana de la app.');

    pedirCodigoALaCampana($tokenDuenio)->assertForbidden();
});

test('un codigo inventado no vincula', function () {
    $user = adminCon2fa();
    codigoQueMuestraLaApp(sesionMovil($user));

    escribirEnLaWeb($user, 'XXX-YYY')->assertSet('errorVinculacion', 'El código no es válido o ya venció. Genera uno nuevo en la campana de la app.');
});

test('pedir un codigo nuevo en la app anula el anterior', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    $viejo = codigoQueMuestraLaApp($token);
    $nuevo = codigoQueMuestraLaApp($token);

    escribirEnLaWeb($user, $viejo)->assertSet('errorVinculacion', 'El código no es válido o ya venció. Genera uno nuevo en la campana de la app.');
    escribirEnLaWeb($user, $nuevo)->assertSet('errorVinculacion', '');
});

test('tras varios intentos fallidos la web deja de aceptar codigos por un momento', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    $codigo = codigoQueMuestraLaApp($token);

    foreach (['AAA-AAA', 'BBB-BBB', 'CCC-CCC', 'DDD-DDD', 'EEE-EEE'] as $inventado) {
        escribirEnLaWeb($user, $inventado);
    }

    escribirEnLaWeb($user, $codigo)->assertSet('errorVinculacion', 'Demasiados intentos. Espera un minuto y genera un código nuevo en la app.');
    pedirCodigoALaCampana($token)->assertForbidden();
});

test('desvincular desde el telefono lo deja sin acceso al codigo', function () {
    $user = adminCon2fa();
    $token = sesionMovil($user);
    escribirEnLaWeb($user, codigoQueMuestraLaApp($token));

    app('auth')->forgetGuards();
    $this->withToken($token)->deleteJson('/api/v1/mobile/2fa/vincular')->assertOk();

    pedirCodigoALaCampana($token)->assertForbidden();
});

test('el estado le dice a la app si ese telefono esta vinculado', function () {
    $token = sesionMovil(adminCon2fa());

    $this->withToken($token)
        ->getJson('/api/v1/mobile/2fa/estado')
        ->assertOk()
        ->assertJsonPath('data.vinculado', false)
        ->assertJsonPath('data.requiere_2fa', true)
        ->assertJsonPath('data.dos_factores_activo', true)
        ->assertJsonPath('data.dos_factores_iniciado', true);
});

test('un acopiador sin 2fa no puede generar codigos de vinculacion ni pedir codigos', function () {
    app(RolePermissionSeeder::class)->run();
    $acopiador = User::factory()->create();
    $acopiador->assignRole('acopiador');
    $token = sesionMovil($acopiador);

    $this->withToken($token)->postJson('/api/v1/mobile/2fa/codigo-vinculacion')->assertStatus(422);

    pedirCodigoALaCampana($token)->assertForbidden();
});

test('sin sesion movil no se puede pedir el codigo', function () {
    $this->getJson('/api/v1/mobile/2fa/codigo')->assertUnauthorized();
    $this->postJson('/api/v1/mobile/2fa/codigo-vinculacion')->assertUnauthorized();
});

test('se puede activar el 2fa usando solo el celular, sin otra aplicacion', function () {
    // Habilitar en la web, vincular la app con su codigo y confirmar con el
    // codigo que muestra la campana.
    app(RolePermissionSeeder::class)->run();
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $token = sesionMovil($supervisor);

    Livewire::actingAs($supervisor)->test(TwoFactorSetup::class)->call('habilitar')->assertSee('Código que muestra tu celular');

    escribirEnLaWeb($supervisor, codigoQueMuestraLaApp($token))->assertSet('errorVinculacion', '');
    $codigoDeLaCampana = pedirCodigoALaCampana($token)->assertOk()->json('data.codigo');

    Livewire::actingAs($supervisor)->test(TwoFactorSetup::class)
        ->set('code', $codigoDeLaCampana)
        ->call('confirmar')
        ->assertSet('error', '');

    expect($supervisor->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('el codigo que muestra la campana del celular sirve para terminar de entrar a la web', function () {
    // Es el recorrido completo: vincular el telefono, leer el codigo en la
    // campana y escribirlo en la verificacion en dos pasos de la web.
    $user = adminCon2fa();
    $token = sesionMovil($user);

    escribirEnLaWeb($user, codigoQueMuestraLaApp($token));
    $codigoDeLaCampana = pedirCodigoALaCampana($token)->assertOk()->json('data.codigo');

    // Lo que sigue ocurre en el navegador, sin la sesion del telefono.
    app('auth')->forgetGuards();
    $this->flushHeaders();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/two-factor-challenge');

    $this->post('/two-factor-challenge', ['code' => $codigoDeLaCampana])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user->fresh());
});

/*
 * La vinculacion cuelga del telefono, no de la sesion: la app manda un
 * identificador propio que conserva aunque su dueño vuelva a entrar.
 */

/** El telefono pide su codigo de vinculacion identificandose. */
function codigoQueMuestraElTelefono(string $token, string $dispositivoId): string
{
    app('auth')->forgetGuards();

    return test()->withToken($token)
        ->withHeader('X-Dispositivo', $dispositivoId)
        ->postJson('/api/v1/mobile/2fa/codigo-vinculacion')
        ->assertCreated()
        ->json('data.codigo');
}

function pedirCodigoDesdeElTelefono(string $token, string $dispositivoId): \Illuminate\Testing\TestResponse
{
    app('auth')->forgetGuards();

    return test()->withToken($token)
        ->withHeader('X-Dispositivo', $dispositivoId)
        ->getJson('/api/v1/mobile/2fa/codigo');
}

test('el telefono vinculado sigue recibiendo su codigo despues de volver a iniciar sesion', function () {
    $user = adminCon2fa();
    $telefono = '7f1c4b2a-9d3e-4a5b-8c6d-0e1f2a3b4c5d';

    escribirEnLaWeb($user, codigoQueMuestraElTelefono(sesionMovil($user), $telefono))->assertSee('Teléfono vinculado');

    // El dueño cierra sesion y vuelve a entrar: la app recibe un token nuevo.
    $user->tokens()->delete();
    $nuevaSesion = sesionMovil($user);

    pedirCodigoDesdeElTelefono($nuevaSesion, $telefono)
        ->assertOk()
        ->assertJsonStructure(['data' => ['codigo', 'segundos_restantes']]);

    app('auth')->forgetGuards();
    test()->withToken($nuevaSesion)->withHeader('X-Dispositivo', $telefono)
        ->getJson('/api/v1/mobile/2fa/estado')->assertJsonPath('data.vinculado', true);

    // La sesion nueva queda anotada como la ultima del mismo telefono.
    expect(DB::table('dispositivos_dos_factores')->where('user_id', $user->id)->count())->toBe(1);
});

test('otro telefono con la misma cuenta no recibe el codigo sin vincularse', function () {
    $user = adminCon2fa();
    $telefono = '7f1c4b2a-9d3e-4a5b-8c6d-0e1f2a3b4c5d';
    $ajeno = 'aaaabbbb-cccc-dddd-eeee-ffff00001111';

    escribirEnLaWeb($user, codigoQueMuestraElTelefono(sesionMovil($user), $telefono))->assertSee('Teléfono vinculado');

    // Quien solo sabe la contraseña entra en su propio telefono y no obtiene nada.
    pedirCodigoDesdeElTelefono(sesionMovil($user), $ajeno)->assertForbidden();
});

test('desvincular desde la app borra ese telefono y no el de al lado', function () {
    $user = adminCon2fa();
    $uno = '11112222-3333-4444-5555-666677778888';
    $dos = '99990000-1111-2222-3333-444455556666';

    escribirEnLaWeb($user, codigoQueMuestraElTelefono(sesionMovil($user), $uno))->assertSee('Teléfono vinculado');
    $sesionDos = sesionMovil($user, 'Segundo telefono');
    escribirEnLaWeb($user, codigoQueMuestraElTelefono($sesionDos, $dos))->assertSee('Teléfono vinculado');

    expect(DB::table('dispositivos_dos_factores')->where('user_id', $user->id)->count())->toBe(2);

    app('auth')->forgetGuards();
    test()->withToken($sesionDos)->withHeader('X-Dispositivo', $dos)
        ->deleteJson('/api/v1/mobile/2fa/vincular')->assertOk();

    expect(DB::table('dispositivos_dos_factores')->where('user_id', $user->id)->pluck('dispositivo_id')->all())->toBe([$uno]);
});

test('un telefono que se identifica no hereda la vinculacion de otro por compartir la sesion', function () {
    $user = adminCon2fa();
    $telefono = '11112222-3333-4444-5555-666677778888';
    $sesion = sesionMovil($user);

    escribirEnLaWeb($user, codigoQueMuestraElTelefono($sesion, $telefono))->assertSee('Teléfono vinculado');

    // Misma sesion, otro identificador: sin vincularse no recibe el codigo.
    pedirCodigoDesdeElTelefono($sesion, 'aaaabbbb-cccc-dddd-eeee-ffff00001111')->assertForbidden();
    pedirCodigoDesdeElTelefono($sesion, $telefono)->assertOk();
});
