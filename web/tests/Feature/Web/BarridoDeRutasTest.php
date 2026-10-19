<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Barrido de todas las pantallas del portal, rol por rol: ninguna debe
 * reventar. Lo que le corresponde al rol responde 200 y lo que no, 403.
 *
 * Admin y supervisor tienen la verificacion en dos pasos obligatoria, asi
 * que hasta que la activan todo los manda a configurarla: ese 302 es la
 * regla funcionando, no un fallo.
 */
function usuarioDeBarrido(string $rol, bool $conDosFactores = false): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create($conDosFactores ? [
        'two_factor_secret' => encrypt('SECRETODEPRUEBA'),
        'two_factor_confirmed_at' => now(),
    ] : []);
    $user->assignRole($rol);

    return $user;
}

const RUTAS_DEL_PORTAL = [
    '/dashboard', '/proveedores', '/acopios', '/calidad', '/calidad/crear',
    '/produccion', '/produccion/crear', '/produccion/recetas',
    '/almacen', '/almacen/catalogo/crear', '/almacen/entradas/crear', '/almacen/salidas/crear',
    '/compras', '/compras/crear', '/ventas', '/ventas/crear',
    '/pedidos', '/pedidos/cobros', '/pagos', '/notificaciones',
];

test('ninguna pantalla del portal revienta para ningun rol', function () {
    $problemas = [];

    foreach (['acopiador', 'calidad', 'produccion', 'ventas', 'almacen', 'repartidor'] as $rol) {
        $usuario = usuarioDeBarrido($rol);

        foreach (RUTAS_DEL_PORTAL as $ruta) {
            $estado = $this->actingAs($usuario)->get($ruta)->getStatusCode();

            if (! in_array($estado, [200, 403], true)) {
                $problemas[] = $rol.' '.$ruta.' -> '.$estado;
            }
        }
    }

    expect($problemas)->toBe([]);
});

test('con el 2FA ya activado, admin y supervisor entran a todo lo suyo', function () {
    $problemas = [];

    foreach (['admin', 'supervisor'] as $rol) {
        $usuario = usuarioDeBarrido($rol, conDosFactores: true);

        foreach (RUTAS_DEL_PORTAL as $ruta) {
            $estado = $this->actingAs($usuario)->get($ruta)->getStatusCode();

            if (! in_array($estado, [200, 403], true)) {
                $problemas[] = $rol.' '.$ruta.' -> '.$estado;
            }
        }
    }

    expect($problemas)->toBe([]);
});

test('sin el 2FA activado, admin y supervisor van a configurarlo', function () {
    foreach (['admin', 'supervisor'] as $rol) {
        $this->actingAs(usuarioDeBarrido($rol))
            ->get('/dashboard')
            ->assertRedirect(route('dos-factores.setup'));
    }
});
