<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/**
 * En Livewire 3 cualquier propiedad publica puede cambiarse desde el
 * navegador ($wire.set). Si una pantalla comprueba a quien pertenece un
 * registro solo al abrirse y despues confia en el id, basta con cambiar ese
 * id para ver o tocar un registro ajeno. Estas pruebas cambian el id tras
 * abrir la pantalla legitimamente, como lo haria alguien desde la consola.
 */
function rolManipulacion(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

function pedidoDe(string $cliente, ?int $repartidorId = null): PedidoModel
{
    $pedido = PedidoModel::factory()->confirmado()->create([
        'cliente_nombre' => $cliente,
        'cliente_telefono' => '70011122',
        'repartidor_id' => $repartidorId,
    ]);
    $producto = ProductoModel::factory()->create(['tipo' => 'producto_terminado']);
    $pedido->items()->create([
        'producto_id' => $producto->id, 'nombre_producto' => $producto->nombre, 'cantidad' => 1, 'precio_unitario' => 10,
    ]);

    return $pedido;
}

test('en la pantalla de gracias no se puede cambiar el pedido para leer el de otro cliente', function () {
    $mio = pedidoDe('Maria Perez');
    $ajeno = pedidoDe('Cliente Ajeno');
    session()->push('tienda.pedidos', $mio->id);

    expect(fn () => Livewire::test(\App\Presentation\Web\Livewire\Tienda\Confirmacion::class, ['pedido' => $mio->id])
        ->set('pedidoId', $ajeno->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('el repartidor no puede cambiar el pedido abierto para ver uno ajeno', function () {
    $repartidor = rolManipulacion('repartidor');
    $mio = pedidoDe('Cliente Propio', $repartidor->id);
    $ajeno = pedidoDe('Cliente Ajeno', rolManipulacion('repartidor')->id);

    expect(fn () => Livewire::actingAs($repartidor)
        ->test(\App\Presentation\Web\Livewire\Pedidos\Detalle::class, ['pedido' => $mio->id])
        ->set('pedidoId', $ajeno->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('el repartidor no puede cambiar el comprobante abierto por uno ajeno', function () {
    $repartidor = rolManipulacion('repartidor');
    $mio = pedidoDe('Cliente Propio', $repartidor->id);
    $ajeno = pedidoDe('Cliente Ajeno', rolManipulacion('repartidor')->id);

    expect(fn () => Livewire::actingAs($repartidor)
        ->test(\App\Presentation\Web\Livewire\Pedidos\Comprobante::class, ['pedido' => $mio->id])
        ->set('pedidoId', $ajeno->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('el acopiador no puede cambiar la movilidad abierta por la de otro', function () {
    $acopiador = rolManipulacion('acopiador');
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $mia = MovilidadModel::create(['nombre' => 'Camion 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true, 'usuario_id' => $acopiador->id]);
    $ajena = MovilidadModel::create(['nombre' => 'Camion 02', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);

    expect(fn () => Livewire::actingAs($acopiador)
        ->test(\App\Presentation\Web\Livewire\Movilidad\Detalle::class, ['movilidadId' => $mia->id])
        ->set('movilidadId', $ajena->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('el acopiador no puede cambiar la movilidad del registro rapido para acopiar en otra', function () {
    $acopiador = rolManipulacion('acopiador');
    $ruta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $mia = MovilidadModel::create(['nombre' => 'Camion 01', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true, 'usuario_id' => $acopiador->id]);
    $ajena = MovilidadModel::create(['nombre' => 'Camion 02', 'tipo' => 'camion', 'ruta_id' => $ruta->id, 'activa' => true]);
    $proveedor = ProveedorModel::factory()->create(['ruta_id' => $ruta->id, 'activo' => true]);

    expect(fn () => Livewire::actingAs($acopiador)
        ->test(\App\Presentation\Web\Livewire\Movilidad\RegistroRapido::class, ['movilidadId' => $mia->id, 'proveedorId' => $proveedor->id])
        ->set('movilidadId', $ajena->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

/**
 * @return array{0: User, 1: MovilidadModel, 2: ProveedorModel, 3: ProveedorModel}
 */
function recorridoConProveedorAjeno(): array
{
    $acopiador = rolManipulacion('acopiador');
    $suRuta = RutaModel::create(['nombre' => 'Huata Centro', 'activa' => true]);
    $otraRuta = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    $mia = MovilidadModel::create(['nombre' => 'Camion 01', 'tipo' => 'camion', 'ruta_id' => $suRuta->id, 'activa' => true, 'usuario_id' => $acopiador->id]);
    $propio = ProveedorModel::factory()->create(['ruta_id' => $suRuta->id, 'activo' => true]);
    $ajeno = ProveedorModel::factory()->create(['ruta_id' => $otraRuta->id, 'activo' => true]);

    return [$acopiador, $mia, $propio, $ajeno];
}

test('el registro rapido no acepta por la url un proveedor de otra ruta', function () {
    [$acopiador, $mia, , $ajeno] = recorridoConProveedorAjeno();

    Livewire::actingAs($acopiador)
        ->test(\App\Presentation\Web\Livewire\Movilidad\RegistroRapido::class, ['movilidadId' => $mia->id, 'proveedorId' => $ajeno->id])
        ->assertForbidden();

    expect(\App\Infrastructure\Acopio\Models\AcopioModel::where('proveedor_id', $ajeno->id)->exists())->toBeFalse();
});

test('no se puede marcar no entrego a un proveedor de otra ruta', function () {
    [$acopiador, $mia, , $ajeno] = recorridoConProveedorAjeno();

    Livewire::actingAs($acopiador)
        ->test(\App\Presentation\Web\Livewire\Movilidad\Detalle::class, ['movilidadId' => $mia->id])
        ->call('abrirNoEntrega', $ajeno->id)
        ->call('confirmarNoEntrega')
        ->assertForbidden();

    expect(\Illuminate\Support\Facades\DB::table('incidencias_recorrido')->where('proveedor_id', $ajeno->id)->exists())->toBeFalse();
});

test('el proveedor no puede pedir el traslado de otro aunque mande su id', function () {
    // El id sale de su propia ficha: lo que llegue del telefono se ignora.
    [, , , $ajeno] = recorridoConProveedorAjeno();
    $destino = RutaModel::create(['nombre' => 'Batallas', 'activa' => true]);

    $suyo = \App\Infrastructure\Proveedor\Models\ProveedorModel::factory()->create([
        'cedula' => '4440001', 'activo' => true,
    ]);
    app(\App\Application\Proveedor\UseCases\CrearCuentaProveedorUseCase::class)->ejecutar($suyo->id);

    app('auth')->forgetGuards();
    Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::query()->findOrFail($suyo->fresh()->user_id), ['*']);

    $this->postJson('/api/v1/mobile/proveedor/cambio-zona', [
        'proveedor_id' => $ajeno->id,
        'ruta_solicitada_id' => $destino->id,
        'fecha_cambio' => now()->format('Y-m-d'),
    ])->assertCreated();

    // La solicitud quedo a nombre de quien la pidio, no del ajeno.
    $this->assertDatabaseHas('solicitudes_cambio_zona', ['proveedor_id' => $suyo->id]);
    $this->assertDatabaseMissing('solicitudes_cambio_zona', ['proveedor_id' => $ajeno->id]);
});

test('con un proveedor de su propia ruta el no entrego sigue funcionando', function () {
    [$acopiador, $mia, $propio] = recorridoConProveedorAjeno();

    Livewire::actingAs($acopiador)
        ->test(\App\Presentation\Web\Livewire\Movilidad\Detalle::class, ['movilidadId' => $mia->id])
        ->call('abrirNoEntrega', $propio->id)
        ->call('confirmarNoEntrega')
        ->assertOk();

    expect(\Illuminate\Support\Facades\DB::table('incidencias_recorrido')->where('proveedor_id', $propio->id)->exists())->toBeTrue();
});

test('el repartidor no puede apagar el filtro de sus entregas para ver los pedidos de todos', function () {
    $repartidor = rolManipulacion('repartidor');
    pedidoDe('Cliente Propio', $repartidor->id);
    pedidoDe('Cliente Ajeno');

    $pantalla = Livewire::actingAs($repartidor)->test(\App\Presentation\Web\Livewire\Pedidos\Index::class)
        ->assertSee('Cliente Propio')
        ->assertDontSee('Cliente Ajeno');

    expect(fn () => $pantalla->set('soloAsignados', false))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('en el catalogo no se puede apuntar la foto a otro archivo para que se borre al cambiarla', function () {
    $almacen = rolManipulacion('almacen');
    $producto = ProductoModel::factory()->create(['foto_path' => 'productos/propia.jpg']);

    expect(fn () => Livewire::actingAs($almacen)
        ->test(\App\Presentation\Web\Livewire\Inventario\CatalogoFormulario::class, ['productoId' => $producto->id])
        ->set('fotoPath', 'calidad/evidencia-de-otro-analisis.jpg'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('la evidencia fotografica de un analisis de calidad no se puede cambiar desde el navegador', function () {
    expect(fn () => Livewire::actingAs(rolManipulacion('calidad'))
        ->test(\App\Presentation\Web\Livewire\Calidad\Formulario::class)
        ->set('fotoPath', 'calidad/foto-de-otro-analisis.jpg'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});
