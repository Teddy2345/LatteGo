<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('inicia sesion y recibe un token personal', function () {
    $user = User::factory()->create(['password' => Hash::make('password123')]);

    $respuesta = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password123',
        'device_name' => 'telefono-de-prueba',
    ]);

    $respuesta->assertCreated()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'roles']]]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'telefono-de-prueba',
    ]);
});

test('rechaza credenciales invalidas', function () {
    $user = User::factory()->create(['password' => Hash::make('password123')]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'incorrecta',
        'device_name' => 'telefono-de-prueba',
    ])->assertUnprocessable();
});

test('el login se limita a 5 intentos por minuto', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => 'nadie@ecolactea.test',
            'password' => 'incorrecta',
            'device_name' => 'telefono-de-prueba',
        ]);
    }

    $this->postJson('/api/v1/login', [
        'email' => 'nadie@ecolactea.test',
        'password' => 'incorrecta',
        'device_name' => 'telefono-de-prueba',
    ])->assertStatus(429);
});

test('revoca el token del dispositivo actual al cerrar sesion', function () {
    $user = User::factory()->create();
    $token = $user->createToken('telefono-de-prueba');

    $this->withHeader('Authorization', "Bearer {$token->plainTextToken}")
        ->postJson('/api/v1/logout')
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
});
