<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('el login enlaza a recuperar la contraseña y la pantalla abre', function () {
    $this->get('/login')->assertOk()->assertSee(route('password.request'), false);

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('¿Olvidaste tu contraseña?');
});

test('pedir el enlace envia el correo de restablecimiento con el mensaje en español', function () {
    Notification::fake();
    $usuario = User::factory()->create(['email' => 'acopio@ecolactea.test']);

    $this->post('/forgot-password', ['email' => 'acopio@ecolactea.test'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.');

    Notification::assertSentTo($usuario, ResetPassword::class);
});

test('con el enlace se abre la pantalla y se guarda la nueva contraseña', function () {
    $usuario = User::factory()->create(['email' => 'planta@ecolactea.test']);
    $token = Password::broker()->createToken($usuario);

    $this->get("/reset-password/{$token}?email=planta@ecolactea.test")
        ->assertOk()
        ->assertSee('Crea una nueva contraseña');

    $this->post('/reset-password', [
        'token' => $token,
        'email' => 'planta@ecolactea.test',
        'password' => 'LecheHuata2026!',
        'password_confirmation' => 'LecheHuata2026!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('LecheHuata2026!', $usuario->fresh()->password))->toBeTrue();
});

test('un enlace vencido o inventado no cambia la contraseña y lo dice en español', function () {
    $usuario = User::factory()->create(['email' => 'planta@ecolactea.test']);
    $anterior = $usuario->password;

    $this->post('/reset-password', [
        'token' => 'inventado',
        'email' => 'planta@ecolactea.test',
        'password' => 'LecheHuata2026!',
        'password_confirmation' => 'LecheHuata2026!',
    ])->assertSessionHasErrors(['email' => 'El enlace para restablecer la contraseña no es válido o ya venció.']);

    expect($usuario->fresh()->password)->toBe($anterior);
});

test('la pantalla para confirmar la contraseña abre para quien ya ingreso', function () {
    $this->actingAs(User::factory()->create())
        ->get('/user/confirm-password')
        ->assertOk()
        ->assertSee('Confirma tu contraseña');
});

test('las credenciales incorrectas y los errores de validacion se muestran en español', function () {
    User::factory()->create(['email' => 'ventas@ecolactea.test']);

    $this->post('/login', ['email' => 'ventas@ecolactea.test', 'password' => 'incorrecta'])
        ->assertSessionHasErrors(['email' => 'El correo o la contraseña no son correctos.']);

    expect(__('validation.required', ['attribute' => __('validation.attributes.cantidadLitros')]))
        ->toBe('El campo cantidad de litros es obligatorio.');
});
