<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Movilidad\Detalle;
use App\Presentation\Web\Livewire\Movilidad\Movilidades;
use App\Presentation\Web\Livewire\Movilidad\RegistroRapido;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioMovilidad(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('el acopiador ve las tarjetas de movilidades con metricas reales', function () {
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);
    $p1 = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);
    $p2 = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);
    AcopioModel::factory()->create([
        'proveedor_id' => $p1->id, 'ruta_id' => $ruta->id, 'movilidad_id' => $camion->id,
        'fecha' => now()->format('Y-m-d'), 'cantidad_litros' => 40,
    ]);
    $acopiador = usuarioMovilidad('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(Movilidades::class)
        ->assertOk()
        ->assertSee('Camión 01')
        ->assertSee('1 / 2')
        ->assertSee('40.0 L');
});

test('un usuario sin acopios.registrar ni reportes.ver no ve movilidades', function () {
    Livewire::actingAs(usuarioMovilidad('calidad'))
        ->test(Movilidades::class)
        ->assertForbidden();
});

test('el detalle de una movilidad ordena a los proveedores por estado del dia', function () {
    $ruta = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Camión 02', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);
    $recogido = ProveedorModel::factory()->create(['nombre' => 'Juan Mamani', 'ruta_id' => $ruta->id, 'activo' => true]);
    $pendiente = ProveedorModel::factory()->create(['nombre' => 'Maria Quispe', 'ruta_id' => $ruta->id, 'activo' => true]);

    AcopioModel::factory()->create([
        'proveedor_id' => $recogido->id, 'ruta_id' => $ruta->id, 'movilidad_id' => $camion->id,
        'fecha' => now()->format('Y-m-d'), 'cantidad_litros' => 32.5,
    ]);
    $acopiador = usuarioMovilidad('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(Detalle::class, ['movilidadId' => $camion->id])
        ->assertOk()
        ->assertSee('Juan Mamani')
        ->assertSee('32.5 L')
        ->assertSee('Maria Quispe')
        ->assertSee('Registrar acopio');
});

test('registrar no entrego marca al proveedor sin crear un acopio', function () {
    $ruta = RutaModel::create(['nombre' => 'Copacabana', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Camión 03', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Ana Flores', 'ruta_id' => $ruta->id, 'activo' => true]);
    $acopiador = usuarioMovilidad('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(Detalle::class, ['movilidadId' => $camion->id])
        ->call('abrirNoEntrega', $proveedor->id)
        ->set('motivoNoEntrega', 'Proveedor ausente')
        ->call('confirmarNoEntrega')
        ->assertSee('No entregó');

    $this->assertDatabaseHas('incidencias_recorrido', [
        'proveedor_id' => $proveedor->id,
        'movilidad_id' => $camion->id,
        'motivo' => 'Proveedor ausente',
    ]);
    expect(AcopioModel::query()->where('proveedor_id', $proveedor->id)->count())->toBe(0);
});

test('el registro rapido crea un acopio con movilidad, ruta y acopiador automaticos', function () {
    $ruta = RutaModel::create(['nombre' => 'Puerto Perez', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Motocarga', 'tipo' => 'motocarga', 'ruta_id' => $ruta->id, 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Pedro Condori', 'ruta_id' => $ruta->id, 'activo' => true]);
    $acopiador = usuarioMovilidad('acopiador');
    $camion->update(['usuario_id' => $acopiador->id]);

    Livewire::actingAs($acopiador)
        ->test(RegistroRapido::class, ['movilidadId' => $camion->id, 'proveedorId' => $proveedor->id])
        ->assertSee('Pedro Condori')
        ->set('litros', 28.0)
        ->call('registrar')
        ->assertRedirect(route('movilidades.detalle', $camion->id));

    $this->assertDatabaseHas('acopios', [
        'proveedor_id' => $proveedor->id,
        'movilidad_id' => $camion->id,
        'ruta_id' => $ruta->id,
        'acopiador_id' => $acopiador->id,
        'cantidad_litros' => 28.00,
    ]);
});

test('el modal de rutas y camiones queda cerrado para quien no planifica', function () {
    Livewire::actingAs(usuarioMovilidad('acopiador'))
        ->test(\App\Presentation\Web\Livewire\Acopio\Index::class)
        ->call('abrirModalRutas')
        ->assertForbidden();
});

test('el acopiador no ve movilidades que no le fueron asignadas', function () {
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    MovilidadModel::create(['nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);

    Livewire::actingAs(usuarioMovilidad('acopiador'))
        ->test(Movilidades::class)
        ->assertOk()
        ->assertDontSee('Camión 01');
});

test('un acopiador no puede ver el detalle de una movilidad ajena', function () {
    $ruta = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Camión 02', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);

    Livewire::actingAs(usuarioMovilidad('acopiador'))
        ->test(Detalle::class, ['movilidadId' => $camion->id])
        ->assertForbidden();
});

test('un acopiador no puede registrar un acopio rapido en una movilidad ajena', function () {
    $ruta = RutaModel::create(['nombre' => 'Copacabana', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Camión 03', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);

    Livewire::actingAs(usuarioMovilidad('acopiador'))
        ->test(RegistroRapido::class, ['movilidadId' => $camion->id, 'proveedorId' => $proveedor->id])
        ->assertForbidden();
});

test('quien ve reportes puede ver el detalle de cualquier movilidad aunque no sea la suya', function () {
    $ruta = RutaModel::create(['nombre' => 'Puerto Perez', 'activa' => true]);
    $camion = MovilidadModel::create(['nombre' => 'Motocarga', 'tipo' => 'motocarga', 'ruta_id' => $ruta->id, 'activa' => true]);

    Livewire::actingAs(usuarioMovilidad('supervisor'))
        ->test(Detalle::class, ['movilidadId' => $camion->id])
        ->assertOk();
});

test('quien planifica asigna un acopiador titular a una movilidad y reasignarlo lo quita de la anterior', function () {
    $ruta1 = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $ruta2 = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    $camion1 = MovilidadModel::create(['nombre' => 'Camión 01', 'tipo' => 'camion', 'ruta_id' => $ruta1->id, 'activa' => true]);
    $camion2 = MovilidadModel::create(['nombre' => 'Camión 02', 'tipo' => 'camion', 'ruta_id' => $ruta2->id, 'activa' => true]);
    $acopiador = usuarioMovilidad('acopiador');

    Livewire::actingAs(usuarioMovilidad('supervisor'))
        ->test(\App\Presentation\Web\Livewire\Acopio\Index::class)
        ->call('asignarUsuario', $camion1->id, (string) $acopiador->id)
        ->assertOk();

    $this->assertDatabaseHas('movilidades', ['id' => $camion1->id, 'usuario_id' => $acopiador->id]);

    Livewire::actingAs(usuarioMovilidad('supervisor'))
        ->test(\App\Presentation\Web\Livewire\Acopio\Index::class)
        ->call('asignarUsuario', $camion2->id, (string) $acopiador->id)
        ->assertOk();

    $this->assertDatabaseHas('movilidades', ['id' => $camion2->id, 'usuario_id' => $acopiador->id]);
    $this->assertDatabaseHas('movilidades', ['id' => $camion1->id, 'usuario_id' => null]);
});
