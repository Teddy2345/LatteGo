<?php

declare(strict_types=1);

use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Tienda\Catalogo;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/**
 * La portada del sitio es la tienda: quien entra ve los productos a la venta,
 * y el personal inicia sesion desde el boton de la cabecera.
 */
function productoALaVenta(string $nombre, array $extra = []): ProductoModel
{
    $producto = ProductoModel::factory()->create(array_merge([
        'tipo' => 'producto_terminado',
        'nombre' => $nombre,
        'precio_referencia' => 22,
    ], $extra));

    MovimientoInventarioModel::factory()->create([
        'producto_id' => $producto->id,
        'tipo' => 'produccion',
        'cantidad' => 10,
    ]);

    return $producto;
}

test('la portada muestra los productos a la venta', function () {
    productoALaVenta('Queso fresco de 500 g');
    productoALaVenta('Yogurt natural');

    $this->get('/')
        ->assertOk()
        ->assertSee('Nuestros productos')
        ->assertSee('Queso fresco de 500 g')
        ->assertSee('Yogurt natural');
});

test('un visitante ve el boton para iniciar sesion', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Iniciar sesión')
        ->assertSee('href="'.route('login').'"', false);
});

test('quien ya inicio sesion ve el boton para ir al panel en lugar del de login', function () {
    app(RolePermissionSeeder::class)->run();
    $user = User::factory()->create();
    $user->assignRole('ventas');

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Ir al panel')
        ->assertSee('href="'.route('dashboard').'"', false)
        ->assertDontSee('Iniciar sesión');
});

test('cada producto sin foto muestra la ilustracion de referencia de su tipo', function () {
    productoALaVenta('Queso madurado de 1 kg');
    productoALaVenta('Queso fresco de 500 g');
    productoALaVenta('Yogurt de frutilla');
    productoALaVenta('Yogurt natural');
    productoALaVenta('Mantequilla artesanal');
    productoALaVenta('Leche pasteurizada en bolsa');

    $html = $this->get('/')->assertOk()->getContent();

    foreach (['queso-madurado', 'queso-fresco', 'yogurt-frutilla', 'yogurt-natural', 'mantequilla', 'leche'] as $ilustracion) {
        expect($html)->toContain("img/productos/{$ilustracion}.svg");
    }
});

test('un producto de tipo desconocido usa la ilustracion por defecto', function () {
    productoALaVenta('Manjar blanco');

    expect($this->get('/')->getContent())->toContain('img/productos/lacteo.svg');
});

test('si el almacen subio una foto real se usa esa y no la ilustracion', function () {
    productoALaVenta('Queso fresco de 500 g', ['foto_path' => 'productos/queso-real.jpg']);

    $html = $this->get('/')->getContent();

    expect($html)->toContain('storage/productos/queso-real.jpg')
        ->and($html)->not->toContain('img/productos/queso-fresco.svg');
});

test('las ilustraciones de referencia existen en el servidor', function () {
    foreach (['queso-madurado', 'queso-fresco', 'yogurt-frutilla', 'yogurt-natural', 'mantequilla', 'leche', 'lacteo'] as $ilustracion) {
        expect(public_path("img/productos/{$ilustracion}.svg"))->toBeFile();
    }
});

test('la cantidad de cada producto arranca en 1', function () {
    // wire:model pisaba el value="1" y el visitante veia la caja vacia.
    $queso = productoALaVenta('Queso fresco de 500 g');

    Livewire::test(Catalogo::class)->assertSet("cantidades.{$queso->id}", 1);
});

test('un producto dado de baja no aparece en la portada', function () {
    productoALaVenta('Queso de prueba', ['activo' => false]);
    productoALaVenta('Queso fresco de 500 g');

    $this->get('/')
        ->assertSee('Queso fresco de 500 g')
        ->assertDontSee('Queso de prueba');
});
