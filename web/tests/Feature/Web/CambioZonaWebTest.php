<?php

declare(strict_types=1);

use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Movilidad\Detalle as MovilidadDetalle;
use App\Presentation\Web\Livewire\Proveedor\SolicitudesCambioZona;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioCambioZona(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('el acopiador solicita un cambio de zona desde el detalle de su movilidad', function () {
    $rutaActual = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $rutaNueva = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);
    $camion = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $rutaActual->id, 'activa' => true,
    ]);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Ana Flores', 'ruta_id' => $rutaActual->id, 'activo' => true]);
    $acopiador = usuarioCambioZona('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(MovilidadDetalle::class, ['movilidadId' => $camion->id])
        ->call('abrirCambioZona', $proveedor->id)
        ->set('rutaSolicitadaId', $rutaNueva->id)
        ->set('motivoCambioZona', 'Se muda a Batallas')
        ->call('confirmarCambioZona')
        ->assertSee('pendiente de aprobación');

    $this->assertDatabaseHas('solicitudes_cambio_zona', [
        'proveedor_id' => $proveedor->id,
        'ruta_actual_id' => $rutaActual->id,
        'ruta_solicitada_id' => $rutaNueva->id,
        'estado' => 'pendiente',
        'solicitado_por' => $acopiador->id,
    ]);
    // La ruta del proveedor NO cambia todavia: falta la aprobacion.
    expect($proveedor->fresh()->ruta_id)->toBe($rutaActual->id);
});

test('no se puede duplicar una solicitud mientras la anterior siga pendiente', function () {
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $otraRuta = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);
    $camion = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true,
    ]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);
    $acopiador = usuarioCambioZona('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(MovilidadDetalle::class, ['movilidadId' => $camion->id])
        ->call('abrirCambioZona', $proveedor->id)
        ->set('rutaSolicitadaId', $otraRuta->id)
        ->call('confirmarCambioZona')
        ->call('abrirCambioZona', $proveedor->id)
        ->set('rutaSolicitadaId', $otraRuta->id)
        ->call('confirmarCambioZona')
        ->assertSee('ya tiene una solicitud de cambio de zona pendiente');

    expect(SolicitudCambioZonaModel::where('proveedor_id', $proveedor->id)->count())->toBe(1);
});

test('aprobar un cambio de zona reasigna al proveedor y lo saca de la lista anterior', function () {
    $rutaActual = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $rutaNueva = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);
    $camionActual = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $rutaActual->id, 'activa' => true,
    ]);
    $camionNuevo = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 02', 'tipo' => 'camion', 'ruta_id' => $rutaNueva->id, 'activa' => true,
    ]);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Pedro Mamani', 'ruta_id' => $rutaActual->id, 'activo' => true]);

    $solicitud = SolicitudCambioZonaModel::create([
        'proveedor_id' => $proveedor->id,
        'ruta_actual_id' => $rutaActual->id,
        'ruta_solicitada_id' => $rutaNueva->id,
        'fecha_cambio' => now()->format('Y-m-d'),
        'estado' => 'pendiente',
        'solicitado_por' => usuarioCambioZona('acopiador')->id,
    ]);

    $admin = usuarioCambioZona('admin');

    Livewire::actingAs($admin)
        ->test(SolicitudesCambioZona::class)
        ->assertSee('Pedro Mamani')
        ->call('aprobar', $solicitud->id)
        ->assertOk();

    $proveedor->refresh();
    expect($proveedor->ruta_id)->toBe($rutaNueva->id);
    $this->assertDatabaseHas('solicitudes_cambio_zona', [
        'id' => $solicitud->id,
        'estado' => 'aprobada',
        'revisado_por' => $admin->id,
    ]);

    // El nuevo camion ahora lo ve en su recorrido; el anterior ya no.
    Livewire::actingAs($admin)
        ->test(MovilidadDetalle::class, ['movilidadId' => $camionNuevo->id])
        ->assertSee('Pedro Mamani');

    Livewire::actingAs($admin)
        ->test(MovilidadDetalle::class, ['movilidadId' => $camionActual->id])
        ->assertDontSee('Pedro Mamani');
});

test('rechazar un cambio de zona no mueve al proveedor', function () {
    $rutaActual = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $rutaNueva = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $rutaActual->id, 'activo' => true]);

    $solicitud = SolicitudCambioZonaModel::create([
        'proveedor_id' => $proveedor->id,
        'ruta_actual_id' => $rutaActual->id,
        'ruta_solicitada_id' => $rutaNueva->id,
        'fecha_cambio' => now()->format('Y-m-d'),
        'estado' => 'pendiente',
        'solicitado_por' => usuarioCambioZona('acopiador')->id,
    ]);

    Livewire::actingAs(usuarioCambioZona('admin'))
        ->test(SolicitudesCambioZona::class)
        ->call('abrirRechazo', $solicitud->id)
        ->set('observacionRechazo', 'El proveedor se retracto')
        ->call('confirmarRechazo');

    expect($proveedor->fresh()->ruta_id)->toBe($rutaActual->id);
    $this->assertDatabaseHas('solicitudes_cambio_zona', [
        'id' => $solicitud->id,
        'estado' => 'rechazada',
        'observacion_revision' => 'El proveedor se retracto',
    ]);
});

test('la bandeja de aprobacion esta cerrada para quien no edita proveedores', function () {
    Livewire::actingAs(usuarioCambioZona('acopiador'))
        ->test(SolicitudesCambioZona::class)
        ->assertForbidden();
});

test('solicitar un cambio de zona avisa a quien supervisa la planta', function () {
    // Sin el aviso, la solicitud queda esperando en una bandeja que nadie
    // sabe que tiene algo: el acopiador la pide desde el campo.
    $rutaActual = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $rutaNueva = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);
    $camion = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $rutaActual->id, 'activa' => true,
    ]);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Ana Flores', 'ruta_id' => $rutaActual->id, 'activo' => true]);
    $acopiador = usuarioCambioZona('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(MovilidadDetalle::class, ['movilidadId' => $camion->id])
        ->call('abrirCambioZona', $proveedor->id)
        ->set('rutaSolicitadaId', $rutaNueva->id)
        ->set('motivoCambioZona', 'Se muda a Batallas')
        ->call('confirmarCambioZona');

    $this->assertDatabaseHas('notificaciones', [
        'tipo' => 'cambio_zona_solicitado',
        'titulo' => 'Cambio de zona por aprobar',
        'mensaje' => 'Ana Flores pide pasar de Huata Centro a Batallas.',
        'nivel' => 'advertencia',
    ]);

    Livewire::actingAs(usuarioCambioZona('admin'))
        ->test(\App\Presentation\Web\Livewire\Notificacion\Centro::class)
        ->assertSee('Cambio de zona por aprobar')
        ->assertSee('Ana Flores')
        ->assertSee('Batallas')
        ->assertSee('Se muda a Batallas');
});

test('una solicitud rechazada por duplicada no genera un segundo aviso', function () {
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $otraRuta = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);
    $camion = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true,
    ]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);
    $acopiador = usuarioCambioZona('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(MovilidadDetalle::class, ['movilidadId' => $camion->id])
        ->call('abrirCambioZona', $proveedor->id)
        ->set('rutaSolicitadaId', $otraRuta->id)
        ->call('confirmarCambioZona')
        ->call('abrirCambioZona', $proveedor->id)
        ->set('rutaSolicitadaId', $otraRuta->id)
        ->call('confirmarCambioZona');

    expect(\App\Infrastructure\Notificacion\Models\NotificacionModel::where('tipo', 'cambio_zona_solicitado')->count())->toBe(1);
});

test('el supervisor puede ver el recorrido pero no solicitar un cambio de zona', function () {
    // Ve el recorrido porque tiene reportes.ver, pero solicitar un cambio de
    // zona es una accion de campo (acopios.registrar), no de reportes.
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $camion = \App\Infrastructure\Movilidad\Models\MovilidadModel::create([
        'nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true,
    ]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);

    Livewire::actingAs(usuarioCambioZona('supervisor'))
        ->test(MovilidadDetalle::class, ['movilidadId' => $camion->id])
        ->call('abrirCambioZona', $proveedor->id)
        ->assertForbidden();
});
