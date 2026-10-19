<?php

declare(strict_types=1);

use App\Application\Proveedor\UseCases\CrearCuentaProveedorUseCase;
use App\Domain\Proveedor\Exceptions\CuentaProveedorException;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

/** Un proveedor con su cuenta de la app ya abierta. */
function proveedorConCuenta(string $cedula = '7378908'): array
{
    app(RolePermissionSeeder::class)->run();

    $proveedor = ProveedorModel::factory()->create([
        'nombre' => 'Juan Mamani', 'cedula' => $cedula, 'telefono' => '77115440',
        'precio_litro' => 1.70, 'activo' => true,
    ]);

    $resultado = app(CrearCuentaProveedorUseCase::class)->ejecutar($proveedor->id);

    return [$proveedor->fresh(), User::query()->findOrFail($proveedor->fresh()->user_id), $resultado['clave']];
}

/** Queso con precio de proveedor mas barato y existencia. */
function quesoDeTienda(float $publico = 18.0, ?float $proveedor = 13.5, float $stock = 40): ProductoModel
{
    $queso = ProductoModel::factory()->create([
        'nombre' => 'Queso fresco', 'unidad' => 'pieza',
        'precio_referencia' => $publico, 'precio_proveedor' => $proveedor, 'activo' => true,
    ]);

    MovimientoInventarioModel::factory()->create([
        'producto_id' => $queso->id, 'tipo' => 'produccion', 'cantidad' => $stock,
    ]);

    return $queso;
}

test('la planta le abre la cuenta y el proveedor entra con su cedula', function () {
    [$proveedor, $user, $clave] = proveedorConCuenta();

    expect($user->hasRole('proveedor'))->toBeTrue()
        ->and($proveedor->user_id)->toBe($user->id);

    app('auth')->forgetGuards();
    $this->postJson('/api/v1/login', [
        'cedula' => '7378908', 'password' => $clave, 'device_name' => 'Celular de Juan',
    ])->assertCreated()->assertJsonPath('data.user.roles.0', 'proveedor');
});

test('no se le abre cuenta dos veces ni a un proveedor dado de baja', function () {
    [$proveedor] = proveedorConCuenta();

    expect(fn () => app(CrearCuentaProveedorUseCase::class)->ejecutar($proveedor->id))
        ->toThrow(CuentaProveedorException::class);

    $otro = ProveedorModel::factory()->create(['cedula' => '9999999', 'activo' => false]);
    expect(fn () => app(CrearCuentaProveedorUseCase::class)->ejecutar($otro->id))
        ->toThrow(CuentaProveedorException::class);
});

test('la tienda de la app le muestra su precio, sin comparaciones ni cupones', function () {
    [, $user] = proveedorConCuenta();
    quesoDeTienda();

    Sanctum::actingAs($user, ['*']);
    $this->getJson('/api/v1/mobile/proveedor/tienda')
        ->assertOk()
        ->assertJsonPath('data.0.precio', 13.5)
        // Ya esta identificado como proveedor: no hay precio al publico que
        // comparar ni descuento que anunciar.
        ->assertJsonMissingPath('data.0.precio_publico')
        ->assertJsonMissingPath('data.0.tiene_descuento');
});

test('el pedido se cobra al precio de proveedor aunque la app mande otro', function () {
    [, $user] = proveedorConCuenta();
    $queso = quesoDeTienda();

    Sanctum::actingAs($user, ['*']);
    $respuesta = $this->postJson('/api/v1/mobile/proveedor/pedidos', [
        'tipo_entrega' => 'delivery',
        'direccion' => 'Comunidad Pucuro Grande',
        // La app intenta colar un precio: se ignora, el precio sale del catalogo.
        'items' => [['producto_id' => $queso->id, 'cantidad' => 2, 'precio_unitario' => 1]],
    ])->assertCreated();

    $pedidoId = $respuesta->json('data.id');

    $this->assertDatabaseHas('items_pedido', [
        'pedido_id' => $pedidoId, 'producto_id' => $queso->id, 'precio_unitario' => 13.5,
    ]);
    expect((float) $respuesta->json('data.total'))->toBe(27.0);
});

test('al comprar le llega a su campana el codigo del pedido', function () {
    [, $user] = proveedorConCuenta();
    $queso = quesoDeTienda();

    Sanctum::actingAs($user, ['*']);
    $codigo = $this->postJson('/api/v1/mobile/proveedor/pedidos', [
        'tipo_entrega' => 'recojo',
        'items' => [['producto_id' => $queso->id, 'cantidad' => 1]],
    ])->assertCreated()->json('data.codigo');

    expect($codigo)->toBeString()->toHaveLength(6);

    $this->assertDatabaseHas('notificaciones', [
        'user_id' => $user->id, 'tipo' => 'pedido_proveedor', 'titulo' => 'Pedido '.$codigo,
    ]);
    $this->assertDatabaseHas('pedidos', ['codigo' => $codigo, 'user_id' => $user->id, 'tipo_entrega' => 'recojo']);
});

test('quien recoge en planta no necesita dar direccion, quien pide delivery si', function () {
    [, $user] = proveedorConCuenta();
    $queso = quesoDeTienda();

    Sanctum::actingAs($user, ['*']);
    $this->postJson('/api/v1/mobile/proveedor/pedidos', [
        'tipo_entrega' => 'recojo', 'items' => [['producto_id' => $queso->id, 'cantidad' => 1]],
    ])->assertCreated();

    $this->postJson('/api/v1/mobile/proveedor/pedidos', [
        'tipo_entrega' => 'delivery', 'direccion' => '', 'items' => [['producto_id' => $queso->id, 'cantidad' => 1]],
    ])->assertStatus(422);
});

test('el proveedor solo ve sus propias notificaciones y sus propios pedidos', function () {
    [, $user] = proveedorConCuenta();
    [, $otro] = proveedorConCuenta('1234567');
    $queso = quesoDeTienda();

    Sanctum::actingAs($user, ['*']);
    $mio = $this->postJson('/api/v1/mobile/proveedor/pedidos', [
        'tipo_entrega' => 'recojo', 'items' => [['producto_id' => $queso->id, 'cantidad' => 1]],
    ])->json('data.codigo');

    app('auth')->forgetGuards();
    Sanctum::actingAs($otro, ['*']);
    $this->getJson('/api/v1/mobile/proveedor/pedidos')->assertOk()->assertJsonCount(0, 'data');

    // La campana del otro no trae el codigo ajeno.
    $suyas = app(\App\Application\Notificacion\UseCases\ListarNotificacionesUseCase::class)
        ->ejecutar($otro->id, 50, soloPropias: true);
    expect(collect($suyas)->pluck('titulo')->all())->not->toContain('Pedido '.$mio);
});

test('una cuenta sin ficha de proveedor no entra a la tienda del proveedor', function () {
    app(RolePermissionSeeder::class)->run();
    $suelto = User::factory()->create();
    $suelto->assignRole('proveedor');

    Sanctum::actingAs($suelto, ['*']);
    $this->getJson('/api/v1/mobile/proveedor/tienda')->assertForbidden();
    $this->getJson('/api/v1/mobile/proveedor/mi-cuenta')->assertForbidden();
});

test('mi cuenta muestra la leche entregada en la semana de pago en curso', function () {
    [$proveedor, $user] = proveedorConCuenta();

    // Jueves a miercoles: se toman dos entregas dentro de la semana en curso.
    $semanaInicio = now()->subDays((now()->dayOfWeekIso - 4 + 7) % 7)->startOfDay();
    foreach ([12.5, 8.0] as $litros) {
        DB::table('acopios')->insert([
            'proveedor_id' => $proveedor->id, 'acopiador_id' => $user->id,
            'ruta_id' => $proveedor->ruta_id, 'fecha' => $semanaInicio->format('Y-m-d'),
            'cantidad_litros' => $litros, 'estado' => 'sincronizado',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    Sanctum::actingAs($user, ['*']);
    $this->getJson('/api/v1/mobile/proveedor/mi-cuenta')
        ->assertOk()
        ->assertJsonPath('data.semana.litros', 20.5)
        ->assertJsonPath('data.semana.entregas', 2)
        ->assertJsonPath('data.semana.monto_estimado', 34.85)
        ->assertJsonPath('data.proveedor.precio_litro', 1.7);
});

test('el pedido del proveedor no deja vender mas de lo que hay', function () {
    [, $user] = proveedorConCuenta();
    $queso = quesoDeTienda(stock: 3);

    Sanctum::actingAs($user, ['*']);
    $this->postJson('/api/v1/mobile/proveedor/pedidos', [
        'tipo_entrega' => 'recojo', 'items' => [['producto_id' => $queso->id, 'cantidad' => 10]],
    ])->assertStatus(422);

    expect(DB::table('pedidos')->count())->toBe(0);
});

test('la pantalla de proveedores abre la cuenta y muestra la clave una sola vez', function () {
    app(RolePermissionSeeder::class)->run();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Rosa Quispe', 'cedula' => '5554443', 'activo' => true]);

    $pantalla = \Livewire\Livewire::actingAs($admin)
        ->test(\App\Presentation\Web\Livewire\Proveedor\Index::class)
        ->call('abrirCuentaApp', $proveedor->id)
        ->assertSet('errorCuenta', '')
        ->assertSee('Acceso de Rosa Quispe a la app');

    $clave = $pantalla->get('claveGenerada');
    expect($clave)->toHaveLength(8);

    // La clave se guarda cifrada, nunca en claro.
    $user = User::query()->findOrFail($proveedor->fresh()->user_id);
    expect($user->password)->not->toBe($clave)
        ->and(Illuminate\Support\Facades\Hash::check($clave, $user->password))->toBeTrue();

    // Pedirla otra vez avisa que ya existe.
    $pantalla->call('cerrarClave')->call('abrirCuentaApp', $proveedor->id)
        ->assertSee('ya tiene su cuenta de la app');
});

test('un acopiador no puede abrirle cuenta de app a un proveedor', function () {
    app(RolePermissionSeeder::class)->run();
    $acopiador = User::factory()->create();
    $acopiador->assignRole('acopiador');

    $proveedor = ProveedorModel::factory()->create(['cedula' => '1112223', 'activo' => true]);

    // Ni siquiera abre la pantalla de proveedores: le falta proveedores.ver.
    \Livewire\Livewire::actingAs($acopiador)
        ->test(\App\Presentation\Web\Livewire\Proveedor\Index::class)
        ->assertForbidden();

    expect($proveedor->fresh()->user_id)->toBeNull();
});

test('la cuenta del proveedor no abre el portal de la planta', function () {
    [, $user] = proveedorConCuenta();

    // La tienda publica sigue abierta para cualquiera, incluido el.
    $this->actingAs($user)->get('/')->assertOk();

    // Pero el portal interno lo saca y le explica por que.
    $this->actingAs($user)->get('/dashboard')
        ->assertRedirect(route('login'));

    expect(auth()->guard('web')->check())->toBeFalse();
});

test('ninguna pantalla del portal se le abre al proveedor', function () {
    [, $user] = proveedorConCuenta();

    foreach (['/dashboard', '/proveedores', '/acopios', '/produccion', '/almacen', '/ventas', '/pedidos', '/pagos'] as $ruta) {
        $this->flushSession();
        app('auth')->forgetGuards();

        $this->actingAs($user)->get($ruta)->assertRedirect(route('login'));
    }
});
