<?php

declare(strict_types=1);

use App\Application\Produccion\UseCases\GestionarRecetasUseCase;
use App\Application\Produccion\UseCases\ListarProduccionUseCase;
use App\Application\Produccion\UseCases\RegistrarLoteDinamicoUseCase;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Produccion\Costeo;
use App\Presentation\Web\Livewire\Produccion\LoteFormulario;
use App\Presentation\Web\Livewire\Produccion\Recetas;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

function prepararLoteDinamico(string $nombre = 'Yogur', string $unidad = 'litro'): array
{
    app(RolePermissionSeeder::class)->run();
    $usuario = User::factory()->create();
    $usuario->assignRole('produccion');
    $producto = ProductoModel::factory()->create(['nombre' => $nombre, 'unidad' => $unidad]);
    $insumo = ProductoModel::factory()->insumo()->create(['nombre' => 'Insumo base', 'unidad' => 'kg']);
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'compra', 'cantidad' => 20, 'precio_unitario' => 3]);
    $receta = app(GestionarRecetasUseCase::class)->crear([
        'nombre' => 'Receta '.$nombre, 'productoId' => $producto->id, 'cantidadBase' => 2,
        'litrosBase' => 0, 'descripcion' => 'Preparación de prueba', 'insumos' => [['productoId' => $insumo->id, 'cantidad' => 1]],
    ], $usuario->id);
    return [$usuario, $producto, $insumo, $receta];
}

function datosLote(ProductoModel $producto, ProductoModel $insumo, int $receta): array
{
    return ['fecha' => now()->format('Y-m-d'), 'productoId' => $producto->id, 'recetaId' => $receta,
        'cantidadPlanificada' => 10, 'cantidadProducida' => 9.5, 'litrosProcesados' => 0,
        'observaciones' => '', 'requestId' => (string) Str::uuid(),
        'insumos' => [['productoId' => $insumo->id, 'cantidad' => 5]],
    ];
}

test('la web produce por cantidad y unidad de cualquier producto', function (string $nombre, string $unidad) {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico($nombre, $unidad);
    Livewire::actingAs($usuario)->test(LoteFormulario::class)
        ->set('productos.0.recetaId', $receta)->set('productos.0.cantidadPlanificada', 10)->call('aplicarRecetas')
        ->assertSet('productos.0.productoId', $producto->id)->assertSet('insumos.0.cantidad', 5.0)
        ->set('productos.0.cantidadProducida', 9.5)->set('insumos.0.cantidad', 5.25)
        ->call('guardar')->assertHasNoErrors()->assertRedirect(route('produccion.index'));
    $lote = ProduccionModel::firstOrFail();
    $this->assertDatabaseHas('produccion_productos', ['produccion_id' => $lote->id, 'cantidad_planificada' => 10, 'cantidad_producida' => 9.5]);
    expect(app(MovimientoInventarioRepository::class)->stockDe($producto->id))->toBe(9.5)
        ->and(app(MovimientoInventarioRepository::class)->stockDe($insumo->id))->toBe(14.75);
    Livewire::actingAs($usuario)->test(\App\Presentation\Web\Livewire\Produccion\Index::class)->assertSee($nombre)->assertSee('9.50');
    Livewire::actingAs($usuario)->test(Costeo::class, ['produccion' => $lote->id])->assertSee($nombre)->assertSee('por '.$unidad);
    expect(app(ListarProduccionUseCase::class)->ejecutar()[0]->resultados[0]['unidad'])->toBe($unidad);
    Livewire::actingAs($usuario)->test(\App\Presentation\Web\Livewire\Dashboard\Index::class)->assertOk();
    expect(fn () => app(\App\Application\Produccion\UseCases\RegistrarDespachoUseCase::class)->ejecutar(
        new \App\Application\Produccion\DTOs\RegistrarDespachoData(produccionId: $lote->id, despachadorId: $usuario->id, quesosRecibidos: 1, quesosDespachados: 1, observaciones: null)
    ))->toThrow(\App\Domain\Produccion\Exceptions\ProduccionException::class);
    Sanctum::actingAs($usuario);
    $this->getJson('/api/v1/produccion/'.$lote->id)->assertOk()->assertJsonPath('data.resultados.0.producto', $nombre);
})->with([['Yogur','litro'], ['Mantequilla','kg'], ['Queso de 2 kg','unidad']]);

test('reintentar un lote no duplica entradas ni consumos y cambiarlo exige una solicitud nueva', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    $datos = datosLote($producto, $insumo, $receta);
    $servicio = app(RegistrarLoteDinamicoUseCase::class);
    $id = $servicio->ejecutar($datos, $usuario->id);
    expect($servicio->ejecutar($datos, $usuario->id))->toBe($id);
    expect(ProduccionModel::count())->toBe(1)->and(MovimientoInventarioModel::count())->toBe(3);
    $datos['cantidadProducida'] = 11;
    expect(fn () => $servicio->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);
    expect(app(MovimientoInventarioRepository::class)->stockDe($producto->id))->toBe(9.5);
});

test('si falta stock no se guarda ningun componente del lote', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    $datos = datosLote($producto, $insumo, $receta);
    $datos['insumos'][0]['cantidad'] = 21;
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);
    expect(ProduccionModel::count())->toBe(0)->and(DB::table('produccion_productos')->count())->toBe(0)
        ->and(DB::table('produccion_detalles')->count())->toBe(0)->and(MovimientoInventarioModel::count())->toBe(1);
});

test('un cambio de cantidad planificada exige recalcular los insumos', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    Livewire::actingAs($usuario)->test(LoteFormulario::class)->set('productos.0.recetaId', $receta)
        ->set('productos.0.cantidadPlanificada', 10)->call('aplicarRecetas')->set('productos.0.cantidadPlanificada', 12)
        ->call('guardar')->assertHasErrors(['productos']);
    expect(ProduccionModel::count())->toBe(0);
});

test('recetas y lotes validan unidades producto precision y consumo', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    $datos = datosLote($producto, $insumo, $receta);
    $datos['cantidadProducida'] = 0.001;
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);
    $datos['cantidadProducida'] = 9.5;
    $producto->update(['unidad' => 'kg']);
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);
    $producto->update(['unidad' => 'litro']);
    $datos['insumos'] = [];
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);
    $datos['insumos'] = [['productoId' => $insumo->id, 'cantidad' => 5], ['productoId' => $insumo->id, 'cantidad' => 5]];
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);
    expect(ProduccionModel::count())->toBe(0);
});

test('la pantalla permite crear recetas de productos nuevos con unidad propia', function () {
    [$usuario, $producto, $insumo] = prepararLoteDinamico('Queso presentación familiar', 'paquete');
    Livewire::actingAs($usuario)->test(Recetas::class)->set('nombre', 'Receta familiar v2')->set('productoId', $producto->id)
        ->set('cantidadBase', 3)->set('litrosBase', 0)->set('insumos', [['productoId' => $insumo->id, 'cantidad' => 2]])
        ->call('guardar')->assertHasNoErrors()->assertSee('3.00 paquete');
    $this->assertDatabaseHas('recetas', ['nombre' => 'Receta familiar v2', 'cantidad_base' => 3]);
});

test('un acopiador no puede abrir ni guardar produccion y la receta antigua exige nueva version', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    $usuario->syncRoles('acopiador');
    Livewire::actingAs($usuario)->test(LoteFormulario::class)->assertForbidden();
    $usuario->syncRoles('produccion');
    DB::table('recetas')->where('id', $receta)->update(['cantidad_base' => null, 'unidad_medida_id' => null]);
    Livewire::actingAs($usuario)->test(LoteFormulario::class)->set('productos.0.recetaId', $receta)
        ->call('aplicarRecetas')->assertHasErrors(['recetaId']);
});

test('un registro manual puede utilizar leche de acopio sin inventar insumos', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    $datos = datosLote($producto, $insumo, $receta);
    $datos['recetaId'] = null;
    $datos['insumos'] = [];
    $datos['litrosProcesados'] = 100;
    app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id);
    expect(app(MovimientoInventarioRepository::class)->stockDe($producto->id))->toBe(9.5);
    expect(DB::table('produccion_detalles')->count())->toBe(0);
});

test('un fallo durante la escritura revierte cabecera resultado entrada y consumo', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    \Illuminate\Support\Facades\Event::listen('eloquent.created: '.MovimientoInventarioModel::class, function ($movimiento) {
        if ($movimiento->tipo === 'uso_produccion') {
            throw new RuntimeException('Fallo simulado');
        }
    });
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar(datosLote($producto, $insumo, $receta), $usuario->id))->toThrow(RuntimeException::class);
    expect(ProduccionModel::count())->toBe(0)->and(DB::table('produccion_productos')->count())->toBe(0)
        ->and(DB::table('produccion_detalles')->count())->toBe(0)->and(MovimientoInventarioModel::count())->toBe(1);
});

test('los insumos no se redondean silenciosamente a cero en un lote pequeno', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    DB::table('receta_detalles')->where('receta_id', $receta)->update(['cantidad' => 0.01]);
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->preparar($receta, 0.01))->toThrow(ValidationException::class);
});

test('el control de stock del lote usa el mismo saldo que Almacen tras ventas y consumos', function () {
    [$usuario, $producto, $insumo, $receta] = prepararLoteDinamico();
    // 20 compradas, menos 12 que salieron por venta: Almacen deja 8 disponibles.
    MovimientoInventarioModel::factory()->create(['producto_id' => $insumo->id, 'tipo' => 'venta', 'cantidad' => 12]);
    expect(app(MovimientoInventarioRepository::class)->stockDe($insumo->id))->toBe(8.0);

    $datos = datosLote($producto, $insumo, $receta);
    $datos['insumos'][0]['cantidad'] = 8.5;
    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))->toThrow(ValidationException::class);

    $datos['insumos'][0]['cantidad'] = 8;
    $datos['requestId'] = (string) Str::uuid();
    app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id);
    expect(app(MovimientoInventarioRepository::class)->stockDe($insumo->id))->toBe(0.0);
});

test('el lote no se guarda con un producto que no es el de la receta', function () {
    // La regla la cubria el formulario de queso, que ya no existe: el
    // producto y su unidad tienen que coincidir con los de la receta.
    [$usuario, , $insumo, $receta] = prepararLoteDinamico();
    $otro = ProductoModel::factory()->create(['nombre' => 'Mantequilla suelta', 'unidad' => 'kg']);

    $datos = datosLote($otro, $insumo, $receta);

    expect(fn () => app(RegistrarLoteDinamicoUseCase::class)->ejecutar($datos, $usuario->id))
        ->toThrow(ValidationException::class);

    expect(ProduccionModel::count())->toBe(0)
        ->and(DB::table('produccion_productos')->count())->toBe(0);
});
