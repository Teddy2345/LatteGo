<?php

declare(strict_types=1);

use App\Application\Compras\UseCases\RegistrarCompraUseCase;
use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Compras\Formulario as CompraFormulario;
use App\Presentation\Web\Livewire\Compras\Index as ComprasIndex;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

function usuarioDeCompras(string $rol = 'almacen'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('comprar insumos los deja disponibles en almacen', function () {
    $usuario = usuarioDeCompras();
    $sal = ProductoModel::factory()->insumo()->create(['nombre' => 'Sal fina', 'unidad' => 'kilo']);
    $cuajo = ProductoModel::factory()->insumo()->create(['nombre' => 'Cuajo líquido', 'unidad' => 'litro']);

    Livewire::actingAs($usuario)
        ->test(CompraFormulario::class)
        ->set('proveedor', 'Distribuidora San Martín')
        ->set('documento', 'B001-1234')
        ->set('items', [
            ['productoId' => $sal->id, 'cantidad' => 10, 'costoUnitario' => 2.50],
            ['productoId' => $cuajo->id, 'cantidad' => 4, 'costoUnitario' => 18.00],
        ])
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertRedirect(route('compras.index'));

    $existencias = app(MovimientoInventarioRepository::class);

    expect($existencias->stockDe($sal->id))->toBe(10.0)
        ->and($existencias->stockDe($cuajo->id))->toBe(4.0);

    // 10 × 2,50 + 4 × 18 = 97
    $this->assertDatabaseHas('compras', ['proveedor' => 'Distribuidora San Martín', 'documento' => 'B001-1234', 'total' => 97.00]);
    $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $sal->id, 'tipo' => 'compra', 'cantidad' => 10.00, 'precio_unitario' => 2.50]);
});

test('no se compra un producto terminado por esta puerta', function () {
    $queso = ProductoModel::factory()->create(['nombre' => 'Queso fresco']);

    Livewire::actingAs(usuarioDeCompras())
        ->test(CompraFormulario::class)
        ->set('proveedor', 'Quien sea')
        ->set('items', [['productoId' => $queso->id, 'cantidad' => 1, 'costoUnitario' => 1]])
        ->call('guardar')
        ->assertHasErrors('items.0.productoId');
});

test('guardar dos veces la misma compra no la duplica', function () {
    $usuario = usuarioDeCompras();
    $sal = ProductoModel::factory()->insumo()->create(['nombre' => 'Sal en grano']);

    // El formulario manda siempre el mismo requestId; si el guardado se
    // reintenta por una conexion mala, la compra no debe entrar dos veces.
    $compra = [
        'fecha' => now()->format('Y-m-d'),
        'proveedor' => 'Mercado central',
        'documento' => null,
        'observaciones' => null,
        'requestId' => (string) Str::uuid(),
        'items' => [['productoId' => $sal->id, 'cantidad' => 5, 'costoUnitario' => 3]],
    ];

    $registrar = app(RegistrarCompraUseCase::class);
    $primera = $registrar->ejecutar($compra, $usuario->id);
    $segunda = $registrar->ejecutar($compra, $usuario->id);

    expect($segunda)->toBe($primera)
        ->and(app(MovimientoInventarioRepository::class)->stockDe($sal->id))->toBe(5.0);

    $this->assertDatabaseCount('compras', 1);
});
test('quien solo mira el almacen no puede comprar', function () {
    // Produccion ve existencias (inventario.ver) pero no compra.
    Livewire::actingAs(usuarioDeCompras('produccion'))
        ->test(CompraFormulario::class)
        ->assertForbidden();
});

test('la lista muestra lo gastado en insumos', function () {
    $usuario = usuarioDeCompras();
    $sal = ProductoModel::factory()->insumo()->create(['nombre' => 'Sal marina']);

    Livewire::actingAs($usuario)->test(CompraFormulario::class)
        ->set('proveedor', 'Proveedor de prueba')
        ->set('items', [['productoId' => $sal->id, 'cantidad' => 3, 'costoUnitario' => 4]])
        ->call('guardar');

    Livewire::actingAs($usuario)->test(ComprasIndex::class)
        ->assertSee('Proveedor de prueba')
        ->assertSee('Sal marina')
        ->assertSee('12.00');
});
