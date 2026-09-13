<?php

declare(strict_types=1);

use App\Models\User;
use App\Presentation\Web\Livewire\Auth\TwoFactorSetup;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

function usuarioQueRequiere2fa(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('supervisor');

    return $user;
}

test('el alta muestra la clave en texto para quien no puede escanear el QR', function () {
    // Sin la clave manual hace falta una camara para dar de alta la cuenta,
    // y quien solo tiene el navegador queda sin poder activar el 2FA.
    $user = usuarioQueRequiere2fa();

    $componente = Livewire::actingAs($user)
        ->test(TwoFactorSetup::class)
        ->call('habilitar');

    $secreto = Crypt::decrypt($user->fresh()->two_factor_secret);

    $componente->assertSee('¿No puedes escanear?', false)
        ->assertSee(trim(chunk_split($secreto, 4, ' ')));
});

test('confirmar el 2FA entrega los codigos de respaldo', function () {
    $user = usuarioQueRequiere2fa();

    $componente = Livewire::actingAs($user)->test(TwoFactorSetup::class)->call('habilitar');

    $secreto = Crypt::decrypt($user->fresh()->two_factor_secret);

    $componente->set('code', (new Google2FA())->getCurrentOtp($secreto))
        ->call('confirmar')
        ->assertSet('error', '')
        ->assertSee('Códigos de respaldo', false);

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull()
        ->and($user->fresh()->recoveryCodes())->toHaveCount(8);
});

test('regenerar los codigos anula los anteriores', function () {
    $user = usuarioQueRequiere2fa();

    $componente = Livewire::actingAs($user)->test(TwoFactorSetup::class)->call('habilitar');
    $secreto = Crypt::decrypt($user->fresh()->two_factor_secret);
    $componente->set('code', (new Google2FA())->getCurrentOtp($secreto))->call('confirmar');

    $anteriores = $user->fresh()->recoveryCodes();

    $componente->call('regenerarCodigos');

    $nuevos = $user->fresh()->recoveryCodes();

    expect($nuevos)->toHaveCount(8)
        ->and(array_intersect($anteriores, $nuevos))->toBe([]);
});

test('un codigo de respaldo sirve para entrar sin la aplicacion de autenticacion', function () {
    $user = usuarioQueRequiere2fa();

    $componente = Livewire::actingAs($user)->test(TwoFactorSetup::class)->call('habilitar');
    $secreto = Crypt::decrypt($user->fresh()->two_factor_secret);
    $componente->set('code', (new Google2FA())->getCurrentOtp($secreto))->call('confirmar');

    $codigo = $user->fresh()->recoveryCodes()[0];

    auth()->logout();
    session()->flush();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/two-factor-challenge');

    $this->post('/two-factor-challenge', ['recovery_code' => $codigo])
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user->fresh());
});
