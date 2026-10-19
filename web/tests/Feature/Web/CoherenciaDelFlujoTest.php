<?php

declare(strict_types=1);

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Pedidos\DTOs\EntregarPedidoData;
use App\Application\Pedidos\UseCases\EntregarPedidoUseCase;
use App\Domain\Acopio\Exceptions\ProveedorDadoDeBajaException;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Las uniones entre un modulo y el siguiente: lo que se acopia, lo que se
 * produce, lo que se entrega y lo que se factura tienen que contar la misma
 * historia. Aqui viven las reglas que cruzan esa frontera.
 */
function usuarioDeFlujo(string $rol = 'admin'): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('a un proveedor dado de baja no se le recibe leche, venga de donde venga', function () {
    $baja = ProveedorModel::factory()->create(['nombre' => 'Ficha cerrada', 'activo' => false]);

    // La pantalla solo ofrece proveedores activos, pero la regla tiene que
    // vivir en el caso de uso: por la API se colaba igual.
    expect(fn () => app(RegistrarAcopioUseCase::class)->ejecutar(new RegistrarAcopioData(
        proveedorId: $baja->id,
        acopiadorId: null,
        rutaId: null,
        fecha: now()->format('Y-m-d'),
        cantidadLitros: 20,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    )))->toThrow(ProveedorDadoDeBajaException::class);

    expect(AcopioModel::query()->where('proveedor_id', $baja->id)->count())->toBe(0);
});

test('a uno activo se le sigue recibiendo con normalidad', function () {
    $activo = ProveedorModel::factory()->create(['nombre' => 'Ficha abierta', 'activo' => true]);

    app(RegistrarAcopioUseCase::class)->ejecutar(new RegistrarAcopioData(
        proveedorId: $activo->id,
        acopiadorId: null,
        rutaId: null,
        fecha: now()->format('Y-m-d'),
        cantidadLitros: 20,
        observaciones: null,
        perdidaLitros: null,
        motivoPerdida: null,
    ));

    expect(AcopioModel::query()->where('proveedor_id', $activo->id)->count())->toBe(1);
});

test('la entrega de un pedido de proveedor queda marcada como tal en el historial', function () {
    $usuario = usuarioDeFlujo('ventas');

    $producto = ProductoModel::factory()->create([
        'nombre' => 'Queso fresco', 'precio_referencia' => 22, 'precio_proveedor' => 15,
    ]);
    MovimientoInventarioModel::factory()->create([
        'producto_id' => $producto->id, 'tipo' => 'produccion', 'cantidad' => 30, 'litros_procesados' => 100,
    ]);

    $pedido = PedidoModel::create([
        'cliente_nombre' => 'Rosa Quispe', 'cliente_telefono' => '71234567',
        'cliente_direccion' => 'Huata centro', 'fecha' => now()->format('Y-m-d'),
        'estado' => 'confirmado', 'repartidor_id' => $usuario->id,
        'codigo' => 'PRV123', 'tipo_cliente' => 'proveedor',
    ]);
    $pedido->items()->create([
        'producto_id' => $producto->id, 'nombre_producto' => 'Queso fresco',
        'cantidad' => 2, 'precio_unitario' => 15,
    ]);

    app(EntregarPedidoUseCase::class)->ejecutar(new EntregarPedidoData(
        id: $pedido->id,
        metodoPago: 'efectivo',
        montoCobrado: 30,
        fechaEntrega: now()->format('Y-m-d'),
    ));

    // Sin esta marca, en el historial se veria un queso de 22 vendido a 15
    // y pareceria un descuento que el vendedor dio por su cuenta.
    $this->assertDatabaseHas('movimientos_inventario', [
        'producto_id' => $producto->id,
        'tipo' => 'venta',
        'precio_unitario' => 15.00,
        'tipo_cliente' => 'proveedor',
        'pedido_id' => $pedido->id,
    ]);
});
