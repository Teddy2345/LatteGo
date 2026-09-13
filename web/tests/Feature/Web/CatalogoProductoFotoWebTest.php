<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Inventario\CatalogoFormulario;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function usuarioAlmacenCatalogo(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('almacen');

    return $user;
}

test('registrar un producto terminado con foto y descripcion las guarda en el catalogo', function () {
    Storage::fake('public');

    Livewire::actingAs(usuarioAlmacenCatalogo())
        ->test(CatalogoFormulario::class)
        ->set('tipo', 'producto_terminado')
        ->set('nombre', 'Queso fresco 500g')
        ->set('unidad', 'pieza')
        ->set('precioReferencia', 22)
        ->set('descripcion', 'Queso fresco artesanal de la planta de Huata.')
        ->set('foto', UploadedFile::fake()->image('queso.jpg'))
        ->call('guardar')
        ->assertRedirect(route('inventario.index'));

    $producto = ProductoModel::where('nombre', 'Queso fresco 500g')->firstOrFail();

    expect($producto->descripcion)->toBe('Queso fresco artesanal de la planta de Huata.')
        ->and($producto->foto_path)->not->toBeNull();

    Storage::disk('public')->assertExists($producto->foto_path);
});

test('editar un producto conserva la foto si no se sube una nueva', function () {
    Storage::fake('public');

    $producto = ProductoModel::factory()->create([
        'tipo' => 'producto_terminado',
        'nombre' => 'Yogurt natural',
        'foto_path' => 'productos/original.jpg',
        'descripcion' => 'Descripción original',
    ]);

    Livewire::actingAs(usuarioAlmacenCatalogo())
        ->test(CatalogoFormulario::class, ['productoId' => $producto->id])
        ->assertSet('descripcion', 'Descripción original')
        ->set('precioReferencia', 15.5)
        ->call('guardar')
        ->assertRedirect(route('inventario.index'));

    $producto->refresh();

    expect($producto->foto_path)->toBe('productos/original.jpg')
        ->and((float) $producto->precio_referencia)->toBe(15.5);
});

test('el tipo de un producto no se puede cambiar al editarlo', function () {
    $producto = ProductoModel::factory()->create(['tipo' => 'insumo', 'nombre' => 'Sal fina']);

    Livewire::actingAs(usuarioAlmacenCatalogo())
        ->test(CatalogoFormulario::class, ['productoId' => $producto->id])
        ->set('tipo', 'producto_terminado')
        ->set('unidad', 'kilo')
        ->set('precioReferencia', 5)
        ->call('guardar');

    expect($producto->fresh()->tipo)->toBe('insumo');
});

test('produccion no puede editar el catalogo', function () {
    app(RolePermissionSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('produccion');
    $producto = ProductoModel::factory()->create();

    Livewire::actingAs($user)
        ->test(CatalogoFormulario::class, ['productoId' => $producto->id])
        ->assertForbidden();
});
