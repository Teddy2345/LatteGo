<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function usuarioDeRuta(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

/** Un pedido confirmado y ya asignado a quien lo va a llevar. */
function pedidoParaRepartir(User $repartidor, string $cliente = 'Ana Flores'): PedidoModel
{
    $producto = ProductoModel::factory()->create(['nombre' => 'Queso fresco', 'precio_referencia' => 22]);

    MovimientoInventarioModel::factory()->create([
        'producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => 50, 'litros_procesados' => 200,
    ]);

    $pedido = PedidoModel::create([
        'cliente_nombre' => $cliente, 'cliente_telefono' => '71234567',
        'cliente_direccion' => 'Av. Circunvalación 120, Huata',
        'fecha' => now()->format('Y-m-d'), 'estado' => 'confirmado',
        'repartidor_id' => $repartidor->id, 'codigo' => 'ABC123', 'tipo_entrega' => 'delivery',
    ]);

    $pedido->items()->create([
        'producto_id' => $producto->id, 'nombre_producto' => 'Queso fresco',
        'cantidad' => 2, 'precio_unitario' => 22,
    ]);

    return $pedido;
}

test('el repartidor ve lo que tiene que llevar, con direccion y monto', function () {
    $repartidor = usuarioDeRuta('repartidor');
    $pedido = pedidoParaRepartir($repartidor);

    Sanctum::actingAs($repartidor, ['*']);

    $this->getJson('/api/v1/mobile/reparto/pedidos')
        ->assertOk()
        ->assertJsonPath('data.0.id', $pedido->id)
        ->assertJsonPath('data.0.cliente', 'Ana Flores')
        ->assertJsonPath('data.0.direccion', 'Av. Circunvalación 120, Huata')
        ->assertJsonPath('data.0.total', 44)
        ->assertJsonPath('data.0.items.0.producto', 'Queso fresco');
});

test('el QR de cobro viaja con la ruta cuando la planta lo tiene cargado', function () {
    Storage::fake('public');

    $repartidor = usuarioDeRuta('repartidor');
    pedidoParaRepartir($repartidor);

    Sanctum::actingAs($repartidor, ['*']);

    // Sin QR cargado, el telefono solo muestra el monto.
    $this->getJson('/api/v1/mobile/reparto/pedidos')->assertOk()->assertJsonPath('cobro.qr_url', null);

    Storage::disk('public')->put(config('ecolactea.cobros.qr_path'), 'imagen-falsa');

    $this->getJson('/api/v1/mobile/reparto/pedidos')
        ->assertOk()
        ->assertJsonPath('cobro.metodos.1.valor', 'yape')
        ->assertJsonPath('cobro.metodos.1.exige_referencia', true);

    expect($this->getJson('/api/v1/mobile/reparto/pedidos')->json('cobro.qr_url'))->toContain('qr-yape');
});

test('entregar cobrando por yape exige el numero de operacion y deja la boleta', function () {
    $repartidor = usuarioDeRuta('repartidor');
    $pedido = pedidoParaRepartir($repartidor);

    Sanctum::actingAs($repartidor, ['*']);

    $this->postJson('/api/v1/mobile/reparto/pedidos/'.$pedido->id.'/entregar', [
        'metodo_pago' => 'yape', 'monto_cobrado' => 44,
    ])->assertStatus(422);

    $respuesta = $this->postJson('/api/v1/mobile/reparto/pedidos/'.$pedido->id.'/entregar', [
        'metodo_pago' => 'yape', 'referencia_pago' => '98765432', 'monto_cobrado' => 44,
    ])->assertOk();

    expect($respuesta->json('data.comprobante'))->toStartWith('B001-');

    $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'entregado', 'referencia_pago' => '98765432']);
    $this->assertDatabaseHas('comprobantes', ['pedido_id' => $pedido->id, 'total' => 44.00]);
});

test('nadie entrega un pedido que no le fue asignado', function () {
    $repartidor = usuarioDeRuta('repartidor');
    $pedido = pedidoParaRepartir($repartidor);
    $otro = usuarioDeRuta('repartidor');

    Sanctum::actingAs($otro, ['*']);

    $this->postJson('/api/v1/mobile/reparto/pedidos/'.$pedido->id.'/entregar', [
        'metodo_pago' => 'efectivo', 'monto_cobrado' => 44,
    ])->assertForbidden();

    $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'confirmado']);
});

test('quien no reparte no abre la ruta de reparto', function () {
    Sanctum::actingAs(usuarioDeRuta('acopiador'), ['*']);

    $this->getJson('/api/v1/mobile/reparto/pedidos')->assertForbidden();
});

test('calidad en el telefono ofrece las entregas con el nombre de quien las trajo', function () {
    $analista = usuarioDeRuta('calidad');
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Rosa Quispe', 'activo' => true]);
    $acopio = AcopioModel::factory()->create([
        'proveedor_id' => $proveedor->id, 'fecha' => now()->format('Y-m-d'), 'cantidad_litros' => 30.5,
    ]);

    Sanctum::actingAs($analista, ['*']);

    $this->getJson('/api/v1/mobile/calidad/pendientes')
        ->assertOk()
        ->assertJsonPath('data.0.id', $acopio->id)
        ->assertJsonPath('data.0.proveedor', 'Rosa Quispe')
        ->assertJsonPath('data.0.litros', 30.5);
});

test('el analisis se guarda con la foto del equipo y la entrega deja de estar pendiente', function () {
    Storage::fake('public');

    $analista = usuarioDeRuta('calidad');
    $proveedor = ProveedorModel::factory()->create(['nombre' => 'Juan Mamani', 'activo' => true]);
    $acopio = AcopioModel::factory()->create([
        'proveedor_id' => $proveedor->id, 'fecha' => now()->format('Y-m-d'), 'cantidad_litros' => 20,
    ]);

    Sanctum::actingAs($analista, ['*']);

    $this->postJson('/api/v1/mobile/calidad', [
        'acopio_id' => $acopio->id, 'proveedor_id' => $proveedor->id,
        'fecha' => now()->format('Y-m-d'),
        'temperatura' => 4.2, 'grasa' => 3.4, 'solidos_no_grasos' => 8.6, 'densidad' => 30.1,
        'proteina' => 3.2, 'lactosa' => 4.7, 'sales' => 0.7, 'agua_agregada' => 0, 'ph' => 6.7,
        'prueba_alcohol_aceptada' => true,
        'foto' => UploadedFile::fake()->image('lactoscan.jpg'),
    ])->assertCreated()->assertJsonPath('data.resultado', 'aceptada');

    $this->assertDatabaseHas('analisis_calidad', ['acopio_id' => $acopio->id, 'proveedor_id' => $proveedor->id]);
    expect(DB::table('analisis_calidad')->where('acopio_id', $acopio->id)->value('foto_path'))->toStartWith('calidad/');

    $this->getJson('/api/v1/mobile/calidad/pendientes')->assertOk()->assertJsonCount(0, 'data');
});

test('un acopiador no registra analisis de calidad desde el telefono', function () {
    Sanctum::actingAs(usuarioDeRuta('acopiador'), ['*']);

    $this->getJson('/api/v1/mobile/calidad/pendientes')->assertForbidden();
});

test('el QR se sirve por el mismo host por el que entro el telefono', function () {
    Storage::fake('public');
    Storage::disk('public')->put(config('ecolactea.cobros.qr_path'), 'imagen-falsa');

    $repartidor = usuarioDeRuta('repartidor');
    pedidoParaRepartir($repartidor);

    Sanctum::actingAs($repartidor, ['*']);

    // La planta cambia de red y el telefono entra por la IP del equipo: la
    // URL del QR tiene que seguir a ese host, no a APP_URL, o la imagen no
    // carga en el celular.
    $respuesta = $this->getJson('http://192.168.100.250:8001/api/v1/mobile/reparto/pedidos');

    expect($respuesta->json('cobro.qr_url'))->toStartWith('http://192.168.100.250:8001/');
});

test('la boleta del pedido se abre desde la app con el token, y solo la propia', function () {
    $repartidor = usuarioDeRuta('repartidor');
    $pedido = pedidoParaRepartir($repartidor);

    Sanctum::actingAs($repartidor, ['*']);

    $entrega = $this->postJson('/api/v1/mobile/reparto/pedidos/'.$pedido->id.'/entregar', [
        'metodo_pago' => 'efectivo', 'monto_cobrado' => 44,
    ])->assertOk();

    // La url que devuelve la entrega tiene que ser una que el token abra.
    $url = $entrega->json('data.comprobante_url');

    expect($url)->toContain('/api/v1/mobile/comprobantes/');

    $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');

    // La de otro reparto, no.
    Sanctum::actingAs(usuarioDeRuta('repartidor'), ['*']);
    $this->getJson($url)->assertForbidden();
});
