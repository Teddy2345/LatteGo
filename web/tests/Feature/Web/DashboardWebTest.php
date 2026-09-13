<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Dashboard\Index;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioDashboard(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('el administrador ve el resumen de pedidos y la tendencia semanal de litros', function () {
    AcopioModel::factory()->create(['fecha' => now()->format('Y-m-d'), 'cantidad_litros' => 40, 'estado' => 'sincronizado']);

    $producto = ProductoModel::factory()->create(['tipo' => 'producto_terminado']);
    $pedidoPendiente = PedidoModel::factory()->create(['cliente_nombre' => 'Cliente Pendiente']);
    $pedidoPendiente->items()->create(['producto_id' => $producto->id, 'nombre_producto' => $producto->nombre, 'cantidad' => 1, 'precio_unitario' => 10]);

    Livewire::actingAs(usuarioDashboard('admin'))
        ->test(Index::class)
        ->assertSee('Pedidos pendientes')
        ->assertSee('Recaudado esta semana')
        ->assertSee('Litros acopiados por semana')
        ->assertSee('40 L')
        ->assertSee('Cliente Pendiente');
});

test('el acopiador no ve la seccion de pedidos ni ventas en el dashboard', function () {
    Livewire::actingAs(usuarioDashboard('acopiador'))
        ->test(Index::class)
        ->assertDontSee('Pedidos pendientes')
        ->assertDontSee('Ventas por semana');
});

test('el repartidor ve el dashboard con el resumen de pedidos por estado', function () {
    Livewire::actingAs(usuarioDashboard('repartidor'))
        ->test(Index::class)
        ->assertSee('Pedidos por estado')
        ->assertOk();
});
