<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Calidad\Models\CalidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Acopio\Formulario;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

/**
 * Quien reincide añadiendo agua a la leche queda suspendido esa semana de
 * pago (jueves a miercoles): no puede entregar ni por la web ni desde la app.
 * La semana del 2026-09-03 (jueves) al 2026-09-09 (miercoles) es la suspendida.
 */
function proveedorSuspendido(): ProveedorModel
{
    $proveedor = ProveedorModel::factory()->create(['activo' => true]);

    CalidadModel::factory()->create([
        'proveedor_id' => $proveedor->id,
        // Misma fecha que el analisis: al azar podia caer en el dia que la prueba registra y contaminarla.
        'acopio_id' => AcopioModel::factory()->create(['proveedor_id' => $proveedor->id, 'fecha' => '2026-09-05'])->id,
        'fecha' => '2026-09-05',
        'resultado' => 'rechazada',
        'motivo_rechazo' => 'adulteracion',
        'sancion_aplicada' => 'suspension_semana',
    ]);

    return $proveedor;
}

function usuarioSuspension(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

function acopioDesdeLaApp(int $proveedorId, string $fecha, ?string $requestId = null): array
{
    return [
        'request_id' => $requestId ?? (string) Str::uuid(),
        'proveedor_id' => $proveedorId,
        'fecha' => $fecha,
        'cantidad_litros' => 40.0,
        'latitud' => -15.8402,
        'longitud' => -69.9231,
        'precision_m' => 8.4,
        'capturado_en' => $fecha.' 08:15:00',
    ];
}

test('un suspendido no puede entregar leche desde la app en su semana', function () {
    $proveedor = proveedorSuspendido();
    Sanctum::actingAs(usuarioSuspension('admin'), ['*']);

    $this->postJson('/api/v1/mobile/acopios', acopioDesdeLaApp($proveedor->id, '2026-09-07'))
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Este proveedor está suspendido por reincidir con agua en la leche. No puede entregar hasta el 2026-09-09.']);

    expect(AcopioModel::query()->where('proveedor_id', $proveedor->id)->where('fecha', '2026-09-07')->exists())->toBeFalse();
});

test('reenviar un acopio que ya se habia aceptado no lo pierde aunque luego lo suspendan', function () {
    // La app descarta de su cola lo que el servidor rechaza: si el reintento
    // de algo ya aceptado fallara, ese acopio desapareceria del telefono.
    $proveedor = ProveedorModel::factory()->create(['activo' => true]);
    Sanctum::actingAs(usuarioSuspension('admin'), ['*']);

    $requestId = (string) Str::uuid();
    $this->postJson('/api/v1/mobile/acopios', acopioDesdeLaApp($proveedor->id, '2026-09-04', $requestId))
        ->assertCreated();

    CalidadModel::factory()->create([
        'proveedor_id' => $proveedor->id,
        // Misma fecha que el analisis: al azar podia caer en el dia que la prueba registra y contaminarla.
        'acopio_id' => AcopioModel::factory()->create(['proveedor_id' => $proveedor->id, 'fecha' => '2026-09-05'])->id,
        'fecha' => '2026-09-05',
        'resultado' => 'rechazada',
        'motivo_rechazo' => 'adulteracion',
        'sancion_aplicada' => 'suspension_semana',
    ]);

    $this->postJson('/api/v1/mobile/acopios', acopioDesdeLaApp($proveedor->id, '2026-09-04', $requestId))
        ->assertCreated();
});

test('un suspendido no puede entregar leche desde la web en su semana', function () {
    $proveedor = proveedorSuspendido();

    Livewire::actingAs(usuarioSuspension('admin'))
        ->test(Formulario::class)
        ->set('proveedorId', $proveedor->id)
        ->set('fecha', '2026-09-08')
        ->set('cantidadLitros', 30.0)
        ->call('guardar')
        ->assertSet('error', 'Este proveedor está suspendido por reincidir con agua en la leche. No puede entregar hasta el 2026-09-09.');

    expect(AcopioModel::query()->where('proveedor_id', $proveedor->id)->where('fecha', '2026-09-08')->exists())->toBeFalse();
});

test('a la semana siguiente vuelve a entregar sin que nadie lo reactive', function () {
    $proveedor = proveedorSuspendido();
    Sanctum::actingAs(usuarioSuspension('admin'), ['*']);

    // 2026-09-10 es jueves: empieza la semana siguiente.
    $this->postJson('/api/v1/mobile/acopios', acopioDesdeLaApp($proveedor->id, '2026-09-10'))
        ->assertCreated();
});

test('la primera vez con agua no lo suspende: puede seguir entregando', function () {
    $proveedor = ProveedorModel::factory()->create(['activo' => true]);
    CalidadModel::factory()->create([
        'proveedor_id' => $proveedor->id,
        // Misma fecha que el analisis: al azar podia caer en el dia que la prueba registra y contaminarla.
        'acopio_id' => AcopioModel::factory()->create(['proveedor_id' => $proveedor->id, 'fecha' => '2026-09-05'])->id,
        'fecha' => '2026-09-05',
        'resultado' => 'rechazada',
        'motivo_rechazo' => 'adulteracion',
        'sancion_aplicada' => 'sin_pago_entrega',
    ]);
    Sanctum::actingAs(usuarioSuspension('admin'), ['*']);

    $this->postJson('/api/v1/mobile/acopios', acopioDesdeLaApp($proveedor->id, '2026-09-07'))
        ->assertCreated();
});
