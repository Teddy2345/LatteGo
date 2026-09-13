<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * El menu lateral se arma con las mismas policies que protegen cada pantalla,
 * asi que estas pruebas fijan la relacion rol -> pantallas alcanzables. Si una
 * pantalla deja de estar en el menu de un rol que si puede entrar por URL, o
 * aparece en el de uno que recibiria 403, esto falla.
 */
function usuarioNavegacion(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    // admin y supervisor quedan atrapados en el alta de 2FA hasta
    // confirmarla; aqui se da por confirmada para poder navegar.
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    return $user;
}

/** @return array<int, string> */
function rutasDelMenu(): array
{
    return [
        'dashboard' => '/dashboard',
        'proveedores' => '/proveedores',
        'cambios-zona' => '/proveedores/cambios-de-zona',
        'movilidades' => '/movilidades',
        'acopios' => '/acopios',
        'calidad' => '/calidad',
        'produccion' => '/produccion',
        'almacen' => '/almacen',
        'ventas' => '/ventas',
        'pedidos' => '/pedidos',
        'pagos' => '/pagos',
        'notificaciones' => '/notificaciones',
    ];
}

dataset('accesos por rol', [
    // Supervisor: su razon de ser es la supervision, asi que alcanza en
    // lectura todas las areas operativas, no solo proveedores y almacen.
    'supervisor' => ['supervisor', [
        'dashboard', 'proveedores', 'cambios-zona', 'movilidades', 'acopios',
        'calidad', 'produccion', 'almacen', 'ventas', 'pedidos', 'pagos', 'notificaciones',
    ]],
    'acopiador' => ['acopiador', ['dashboard', 'movilidades', 'acopios']],
    'calidad' => ['calidad', ['dashboard', 'calidad']],
    'produccion' => ['produccion', ['dashboard', 'produccion', 'almacen']],
    'ventas' => ['ventas', ['dashboard', 'almacen', 'ventas', 'pedidos']],
    'almacen' => ['almacen', ['dashboard', 'almacen']],
    'repartidor' => ['repartidor', ['dashboard', 'pedidos']],
]);

test('cada rol alcanza exactamente las pantallas de su menu', function (string $rol, array $permitidas) {
    $user = usuarioNavegacion($rol);

    foreach (rutasDelMenu() as $clave => $url) {
        $esperado = in_array($clave, $permitidas, true) ? 200 : 403;

        $this->actingAs($user)->get($url)->assertStatus($esperado);
    }
})->with('accesos por rol');

test('el menu le ofrece al supervisor las areas que si puede abrir', function () {
    $this->actingAs(usuarioNavegacion('supervisor'))
        ->get('/dashboard')
        ->assertSee('href="'.url('/movilidades').'"', false)
        ->assertSee('href="'.url('/acopios').'"', false)
        ->assertSee('href="'.url('/calidad').'"', false)
        ->assertSee('href="'.url('/produccion').'"', false)
        ->assertSee('href="'.url('/ventas').'"', false)
        ->assertSee('href="'.url('/pagos').'"', false);
});

test('el supervisor mira las areas operativas sin botones que le darian 403', function () {
    // Solo supervisa: no acopia, no analiza, no produce, no genera planillas.
    $supervisor = usuarioNavegacion('supervisor');

    $this->actingAs($supervisor)->get('/acopios')->assertDontSee('Nuevo acopio');
    $this->actingAs($supervisor)->get('/calidad')->assertDontSee('Nuevo analisis');
    $this->actingAs($supervisor)->get('/produccion')->assertDontSee('Nueva producción', false);
    $this->actingAs($supervisor)->get('/pagos')->assertDontSee('Generar planilla');
    $this->actingAs($supervisor)->get('/ventas')->assertDontSee('Registrar venta');
    // Puede editar proveedores, pero borrarlos exige proveedores.eliminar.
    $this->actingAs($supervisor)->get('/proveedores')->assertDontSee('Eliminar');
});

test('el menu no le ofrece al acopiador areas que no puede abrir', function () {
    $this->actingAs(usuarioNavegacion('acopiador'))
        ->get('/dashboard')
        ->assertDontSee('href="'.url('/pagos').'"', false)
        ->assertDontSee('href="'.url('/ventas').'"', false)
        ->assertDontSee('href="'.url('/calidad').'"', false)
        ->assertDontSee('href="'.url('/produccion').'"', false);
});
