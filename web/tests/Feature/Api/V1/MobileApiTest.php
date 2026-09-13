<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

function autenticarMovil(string $rol = 'admin'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    Sanctum::actingAs($user);

    return $user;
}

/**
 * @return array<string, mixed>
 */
function acopioMovilValido(array $sobrescribir = []): array
{
    $proveedor = ProveedorModel::factory()->create();

    return array_merge([
        'request_id' => (string) Str::uuid(),
        'proveedor_id' => $proveedor->id,
        'fecha' => '2026-09-07',
        'cantidad_litros' => 42.5,
        'latitud' => -15.8402,
        'longitud' => -69.9231,
        'precision_m' => 8.4,
        'capturado_en' => '2026-09-07 08:15:00',
    ], $sobrescribir);
}

test('el login entrega las acciones y el perfil que la app usa para armar el menu', function () {
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create(['password' => bcrypt('password')]);
    $user->assignRole('acopiador');

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Ecolactea Android',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user.acciones', ['acopio'])
        ->assertJsonPath('data.user.perfil', 'ACOPIADOR · HUATA');
});

test('el administrador recibe todas las acciones disponibles', function () {
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create(['password' => bcrypt('password')]);
    $user->assignRole('admin');

    $respuesta = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Ecolactea Android',
    ])->assertCreated();

    expect($respuesta->json('data.user.acciones'))
        ->toContain('acopio', 'consolidado', 'auditoria', 'proveedores', 'calidad', 'despachos', 'pagos');
});

test('el catalogo entrega proveedores activos y zonas sin exponer datos sensibles', function () {
    autenticarMovil('acopiador');
    ProveedorModel::factory()->count(2)->create(['activo' => true]);
    ProveedorModel::factory()->create(['activo' => false]);
    RutaModel::create(['nombre' => 'Zona Norte', 'activa' => true]);
    RutaModel::create(['nombre' => 'Zona Sur', 'activa' => true]);

    $respuesta = $this->getJson('/api/v1/mobile/catalogo')
        ->assertOk()
        ->assertJsonCount(2, 'data.proveedores')
        ->assertJsonCount(2, 'data.zonas');

    expect(array_keys($respuesta->json('data.proveedores.0')))->toBe(['id', 'nombre']);
});

test('registra un acopio movil con su ubicacion y lo deja sincronizado', function () {
    $user = autenticarMovil('acopiador');
    $datos = acopioMovilValido();

    $this->postJson('/api/v1/mobile/acopios', $datos)->assertCreated();

    $this->assertDatabaseHas('acopios', [
        'request_id' => $datos['request_id'],
        'acopiador_id' => $user->id,
        'estado' => 'sincronizado',
        'latitud' => '-15.8402000',
        'longitud' => '-69.9231000',
    ]);
});

test('reenviar el mismo request_id no duplica el acopio', function () {
    autenticarMovil('acopiador');
    $datos = acopioMovilValido();

    $this->postJson('/api/v1/mobile/acopios', $datos)->assertCreated();
    $this->postJson('/api/v1/mobile/acopios', $datos)->assertCreated();

    expect(AcopioModel::query()->where('request_id', $datos['request_id'])->count())->toBe(1);
});

test('rechaza un acopio movil sin ubicacion', function () {
    autenticarMovil('acopiador');
    $datos = acopioMovilValido();
    unset($datos['latitud'], $datos['longitud']);

    $this->postJson('/api/v1/mobile/acopios', $datos)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitud', 'longitud']);
});

test('rechaza una ubicacion demasiado imprecisa para servir de trazabilidad', function () {
    autenticarMovil('acopiador');

    $this->postJson('/api/v1/mobile/acopios', acopioMovilValido(['precision_m' => 250]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['precision_m']);
});

test('rechaza una perdida mayor a los litros recibidos', function () {
    autenticarMovil('acopiador');

    $this->postJson('/api/v1/mobile/acopios', acopioMovilValido([
        'cantidad_litros' => 10,
        'perdida_litros' => 12,
        'motivo_perdida' => 'Derrame en transporte',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['perdida_litros']);
});

test('el acopiador solo ve sus propios acopios', function () {
    $user = autenticarMovil('acopiador');
    AcopioModel::factory()->count(2)->create(['acopiador_id' => $user->id]);
    AcopioModel::factory()->count(3)->create();

    $this->getJson('/api/v1/mobile/acopios')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('quien ve reportes recibe los acopios de toda la planta con nombres resueltos', function () {
    autenticarMovil('admin');
    $ruta = RutaModel::create(['nombre' => 'Zona Huata Norte']);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Agustina Tejeda']);
    AcopioModel::factory()->create([
        'proveedor_id' => $proveedor->id,
        'ruta_id' => $ruta->id,
        'cantidad_litros' => 30,
    ]);

    $this->getJson('/api/v1/mobile/acopios')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.proveedor', 'Agustina Tejeda')
        ->assertJsonPath('data.0.zona', 'Zona Huata Norte')
        ->assertJsonPath('data.0.nombre', 'Agustina Tejeda · 30.00 L');
});

test('el consolidado agrupa el litraje del dia por zona', function () {
    autenticarMovil('admin');
    $norte = RutaModel::create(['nombre' => 'Zona Norte']);
    $sur = RutaModel::create(['nombre' => 'Zona Sur']);

    AcopioModel::factory()->create(['ruta_id' => $norte->id, 'fecha' => '2026-09-07', 'cantidad_litros' => 40]);
    AcopioModel::factory()->create(['ruta_id' => $norte->id, 'fecha' => '2026-09-07', 'cantidad_litros' => 60]);
    AcopioModel::factory()->create(['ruta_id' => $sur->id, 'fecha' => '2026-09-07', 'cantidad_litros' => 25]);
    AcopioModel::factory()->create(['ruta_id' => $sur->id, 'fecha' => '2026-09-06', 'cantidad_litros' => 999]);

    $this->getJson('/api/v1/mobile/consolidado?fecha=2026-09-07')
        ->assertOk()
        ->assertJsonPath('data.total_litros', 125)
        ->assertJsonPath('data.total_registros', 3)
        ->assertJsonCount(2, 'data.zonas')
        ->assertJsonPath('data.zonas.0.zona', 'Zona Norte')
        ->assertJsonPath('data.zonas.0.litros', 100)
        ->assertJsonPath('data.zonas.0.registros', 2);
});

test('el acopiador de campo no puede consultar el consolidado de planta', function () {
    autenticarMovil('acopiador');

    $this->getJson('/api/v1/mobile/consolidado')->assertForbidden();
});

test('la auditoria lista la actividad reciente para quien ve reportes', function () {
    autenticarMovil('admin');
    AcopioModel::factory()->count(2)->create();

    $respuesta = $this->getJson('/api/v1/mobile/auditoria')->assertOk();

    expect($respuesta->json('data'))->not->toBeEmpty()
        ->and($respuesta->json('data.0'))->toHaveKeys(['id', 'nombre', 'modulo', 'evento', 'usuario', 'fecha']);
});

test('la auditoria queda cerrada para el acopiador', function () {
    autenticarMovil('acopiador');

    $this->getJson('/api/v1/mobile/auditoria')->assertForbidden();
});

test('la ficha de proveedor entrega sus datos completos y el historial de litraje', function () {
    autenticarMovil('acopiador');
    $zonaActual = RutaModel::create(['nombre' => 'Zona Huata Centro']);
    $zonaAnterior = RutaModel::create(['nombre' => 'Zona Huata Norte']);
    $proveedor = ProveedorModel::factory()->create([
        'nombre' => 'Alejandra Centeno',
        'ruta_id' => $zonaActual->id,
        'precio_litro' => 3.8,
    ]);
    AcopioModel::factory()->create(['proveedor_id' => $proveedor->id, 'ruta_id' => $zonaAnterior->id, 'fecha' => '2026-09-01', 'cantidad_litros' => 40]);
    AcopioModel::factory()->create(['proveedor_id' => $proveedor->id, 'ruta_id' => $zonaActual->id, 'fecha' => '2026-09-08', 'cantidad_litros' => 55]);

    $this->getJson("/api/v1/mobile/proveedores/{$proveedor->id}")
        ->assertOk()
        ->assertJsonPath('data.nombre', 'Alejandra Centeno')
        ->assertJsonPath('data.precio_litro', 3.8)
        ->assertJsonPath('data.zona', 'Zona Huata Centro')
        ->assertJsonCount(2, 'data.historial')
        // Mas reciente primero.
        ->assertJsonPath('data.historial.0.zona', 'Zona Huata Centro')
        ->assertJsonPath('data.historial.0.cantidad_litros', 55)
        ->assertJsonPath('data.historial.1.zona', 'Zona Huata Norte');
});

test('la ficha de proveedor queda cerrada para quien no acopia ni ve reportes', function () {
    autenticarMovil('calidad');
    $proveedor = ProveedorModel::factory()->create();

    $this->getJson("/api/v1/mobile/proveedores/{$proveedor->id}")->assertForbidden();
});

test('solicitar un traslado de zona crea la solicitud pendiente para ese proveedor', function () {
    autenticarMovil('acopiador');
    $proveedor = ProveedorModel::factory()->create();
    $nuevaZona = RutaModel::create(['nombre' => 'Zona Copacabana']);

    $this->postJson("/api/v1/mobile/proveedores/{$proveedor->id}/cambio-zona", [
        'ruta_solicitada_id' => $nuevaZona->id,
        'fecha_cambio' => '2026-09-15',
        'motivo' => 'El proveedor se muda a otra comunidad.',
    ])->assertCreated()->assertJsonPath('data.estado', 'pendiente');

    $this->assertDatabaseHas('solicitudes_cambio_zona', [
        'proveedor_id' => $proveedor->id,
        'ruta_solicitada_id' => $nuevaZona->id,
        'estado' => 'pendiente',
    ]);
});

test('no se puede solicitar un segundo traslado mientras el primero sigue pendiente', function () {
    autenticarMovil('acopiador');
    $proveedor = ProveedorModel::factory()->create();
    $zona = RutaModel::create(['nombre' => 'Zona Copacabana']);
    $payload = ['ruta_solicitada_id' => $zona->id, 'fecha_cambio' => '2026-09-15', 'motivo' => null];

    $this->postJson("/api/v1/mobile/proveedores/{$proveedor->id}/cambio-zona", $payload)->assertCreated();
    $this->postJson("/api/v1/mobile/proveedores/{$proveedor->id}/cambio-zona", $payload)
        ->assertStatus(422)
        ->assertJsonPath('message', 'Este proveedor ya tiene una solicitud de cambio de zona pendiente de revision.');
});

test('quien no acopia no puede solicitar un traslado de zona', function () {
    autenticarMovil('ventas');
    $proveedor = ProveedorModel::factory()->create();
    $zona = RutaModel::create(['nombre' => 'Zona Copacabana']);

    $this->postJson("/api/v1/mobile/proveedores/{$proveedor->id}/cambio-zona", [
        'ruta_solicitada_id' => $zona->id,
        'fecha_cambio' => '2026-09-15',
    ])->assertForbidden();
});
