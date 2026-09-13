<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarInventario(string $rol = 'admin'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    Sanctum::actingAs($user);

    return $user;
}

test('el catalogo de productos entrega el stock disponible', function () {
    autenticarInventario();
    $producto = ProductoModel::factory()->create(['nombre' => 'Queso fresco', 'unidad' => 'pieza']);

    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 100]);
    MovimientoInventarioModel::factory()->venta()->create(['producto_id' => $producto->id, 'cantidad' => 30]);

    $this->getJson('/api/v1/mobile/productos')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nombre', 'Queso fresco')
        ->assertJsonPath('data.0.unidad', 'pieza')
        ->assertJsonPath('data.0.stock', 70);
});

test('el catalogo omite los productos dados de baja', function () {
    autenticarInventario();
    ProductoModel::factory()->create();
    ProductoModel::factory()->inactivo()->create();

    $this->getJson('/api/v1/mobile/productos')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('registrar una transformacion ingresa producto al almacen', function () {
    $user = autenticarInventario('produccion');
    $producto = ProductoModel::factory()->create();

    $this->postJson('/api/v1/mobile/produccion', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 40,
        'litros_procesados' => 320,
        'observaciones' => 'Lote de la manana',
    ])
        ->assertCreated()
        ->assertJsonPath('data.tipo', 'produccion')
        ->assertJsonPath('data.cantidad', 40)
        // 320 litros para 40 piezas: 8 litros por unidad.
        ->assertJsonPath('data.litros_por_unidad', 8);

    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $producto->id,
        'tipo' => 'produccion',
        'usuario_id' => $user->id,
    ]);
});

test('registrar una venta descuenta del almacen y calcula el total', function () {
    autenticarInventario();
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 50]);

    $this->postJson('/api/v1/mobile/ventas', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 12,
        'precio_unitario' => 22.5,
        'cliente' => 'Mercado Rodriguez',
    ])
        ->assertCreated()
        ->assertJsonPath('data.tipo', 'venta')
        ->assertJsonPath('data.total', 270)
        ->assertJsonPath('data.cliente', 'Mercado Rodriguez');

    $this->getJson('/api/v1/mobile/productos')
        ->assertOk()
        ->assertJsonPath('data.0.stock', 38);
});

test('no se puede vender mas de lo que hay en existencia', function () {
    autenticarInventario();
    $producto = ProductoModel::factory()->create(['nombre' => 'Queso madurado', 'unidad' => 'pieza']);
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 5]);

    $this->postJson('/api/v1/mobile/ventas', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 9,
        'precio_unitario' => 30,
    ])->assertStatus(422);

    // El movimiento rechazado no deja rastro en el almacen.
    expect(MovimientoInventarioModel::query()->where('tipo', 'venta')->count())->toBe(0);
});

test('reenviar el mismo request_id no duplica la transformacion', function () {
    autenticarInventario('produccion');
    $producto = ProductoModel::factory()->create();

    $payload = [
        'request_id' => 'transformacion-001',
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 10,
        'litros_procesados' => 80,
    ];

    $this->postJson('/api/v1/mobile/produccion', $payload)->assertCreated();
    $this->postJson('/api/v1/mobile/produccion', $payload)->assertCreated();

    expect(MovimientoInventarioModel::query()->where('request_id', 'transformacion-001')->count())->toBe(1);
});

test('lista transformaciones y ventas por separado', function () {
    autenticarInventario();
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->count(3)->create(['producto_id' => $producto->id, 'cantidad' => 40]);
    MovimientoInventarioModel::factory()->venta()->count(2)->create(['producto_id' => $producto->id, 'cantidad' => 5]);

    $this->getJson('/api/v1/mobile/produccion')->assertOk()->assertJsonCount(3, 'data');
    $this->getJson('/api/v1/mobile/ventas')->assertOk()->assertJsonCount(2, 'data');
});

test('el acopiador no tiene acceso al almacen', function () {
    autenticarInventario('acopiador');

    $this->getJson('/api/v1/mobile/productos')->assertForbidden();
    $this->getJson('/api/v1/mobile/ventas')->assertForbidden();
});

test('el operador de planta ve el stock pero no el historial de ventas', function () {
    autenticarInventario('produccion');

    $this->getJson('/api/v1/mobile/productos')->assertOk();
    $this->getJson('/api/v1/mobile/ventas')->assertForbidden();
});

test('la encargada de ventas usa el almacen desde la app pero no transforma', function () {
    autenticarInventario('ventas');
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 30]);

    $this->getJson('/api/v1/mobile/productos')->assertOk();
    $this->getJson('/api/v1/mobile/ventas')->assertOk();

    $this->postJson('/api/v1/mobile/ventas', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 8,
        'precio_unitario' => 14,
    ])->assertCreated();

    $this->postJson('/api/v1/mobile/produccion', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 5,
        'litros_procesados' => 40,
    ])->assertForbidden();
});

test('el login de ventas habilita solo stock y ventas en la app', function () {
    app(RolePermissionSeeder::class)->run();

    $encargada = User::factory()->create(['password' => bcrypt('password')]);
    $encargada->assignRole('ventas');

    $respuesta = $this->postJson('/api/v1/login', [
        'email' => $encargada->email,
        'password' => 'password',
        'device_name' => 'Ecolactea Android',
    ])->assertCreated();

    expect($respuesta->json('data.user.acciones'))
        ->toEqualCanonicalizing(['stock', 'ventas'])
        ->and($respuesta->json('data.user.perfil'))->toBe('VENTAS · HUATA');
});

test('el operador de planta puede transformar pero no vender', function () {
    autenticarInventario('produccion');
    $producto = ProductoModel::factory()->create();
    MovimientoInventarioModel::factory()->create(['producto_id' => $producto->id, 'cantidad' => 50]);

    $this->postJson('/api/v1/mobile/produccion', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 5,
        'litros_procesados' => 40,
    ])->assertCreated();

    $this->postJson('/api/v1/mobile/ventas', [
        'producto_id' => $producto->id,
        'fecha' => '2026-09-07',
        'cantidad' => 5,
        'precio_unitario' => 20,
    ])->assertForbidden();
});

test('el login habilita planta, stock y ventas segun el rol', function () {
    app(RolePermissionSeeder::class)->run();

    $operador = User::factory()->create(['password' => bcrypt('password')]);
    $operador->assignRole('produccion');

    $respuesta = $this->postJson('/api/v1/login', [
        'email' => $operador->email,
        'password' => 'password',
        'device_name' => 'Ecolactea Android',
    ])->assertCreated();

    expect($respuesta->json('data.user.acciones'))
        ->toContain('planta', 'stock')
        ->not->toContain('ventas');
});
