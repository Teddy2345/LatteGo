<?php

declare(strict_types=1);

use App\Application\Inventario\DTOs\RegistrarProductoData;
use App\Application\Inventario\UseCases\RegistrarProductoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoData;
use App\Application\Pedidos\DTOs\ItemCarritoData;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\Exceptions\ProductoNoEncontradoException;
use App\Domain\Inventario\Exceptions\StockInsuficienteException;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Pedidos\Exceptions\PedidoYaRevisadoException;
use Tests\Support\InMemoryMovimientoInventarioRepository;
use Tests\Support\InMemoryPedidoRepository;
use Tests\Support\InMemoryProductoRepository;

/**
 * @return array{0: CrearPedidoUseCase, 1: ConfirmarPedidoUseCase, 2: CancelarPedidoUseCase, 3: InMemoryProductoRepository, 4: InMemoryMovimientoInventarioRepository, 5: InMemoryPedidoRepository}
 */
function contextoTienda(): array
{
    $productoRepo = new InMemoryProductoRepository();
    $movimientoRepo = new InMemoryMovimientoInventarioRepository();
    $pedidoRepo = new InMemoryPedidoRepository();

    return [
        new CrearPedidoUseCase($pedidoRepo, $productoRepo, $movimientoRepo),
        new ConfirmarPedidoUseCase($pedidoRepo),
        new CancelarPedidoUseCase($pedidoRepo),
        $productoRepo,
        $movimientoRepo,
        $pedidoRepo,
    ];
}

function darStock(InMemoryMovimientoInventarioRepository $movimientos, int $productoId, float $cantidad): void
{
    $movimientos->guardar(MovimientoInventario::compra(
        productoId: $productoId,
        fecha: new DateTimeImmutable('2026-09-01'),
        cantidad: new CantidadProducto($cantidad),
        lote: null,
        observaciones: null,
        usuarioId: null,
    ));
}

test('crea un pedido pendiente con el total calculado de sus items', function () {
    [$crear, , , $productoRepo, $movimientoRepo] = contextoTienda();

    $queso = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Queso fresco',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'pieza',
        precioReferencia: 20,
        stockMinimo: null,
    ));
    $yogurt = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Yogurt natural',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'litro',
        precioReferencia: 12,
        stockMinimo: null,
    ));
    darStock($movimientoRepo, $queso->id, 10);
    darStock($movimientoRepo, $yogurt->id, 10);

    $pedido = $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-14',
        observaciones: 'Tocar el timbre',
        items: [
            new ItemCarritoData(productoId: $queso->id, cantidad: 2),
            new ItemCarritoData(productoId: $yogurt->id, cantidad: 3),
        ],
    ));

    expect($pedido->estado)->toBe('pendiente')
        ->and($pedido->items)->toHaveCount(2)
        ->and($pedido->total)->toBe(76.0); // 2*20 + 3*12
});

test('no se puede pedir mas de lo que hay en existencia', function () {
    [$crear, , , $productoRepo, $movimientoRepo] = contextoTienda();

    $queso = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Queso fresco',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'pieza',
        precioReferencia: 20,
        stockMinimo: null,
    ));
    darStock($movimientoRepo, $queso->id, 2);

    expect(fn () => $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-14',
        observaciones: null,
        items: [new ItemCarritoData(productoId: $queso->id, cantidad: 5)],
    )))->toThrow(StockInsuficienteException::class);
});

test('un carrito vacio no puede convertirse en pedido', function () {
    [$crear] = contextoTienda();

    expect(fn () => $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-14',
        observaciones: null,
        items: [],
    )))->toThrow(App\Domain\Pedidos\Exceptions\CarritoVacioException::class);
});

test('un producto que no existe rechaza el pedido', function () {
    [$crear] = contextoTienda();

    expect(fn () => $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-14',
        observaciones: null,
        items: [new ItemCarritoData(productoId: 999, cantidad: 1)],
    )))->toThrow(ProductoNoEncontradoException::class);
});

test('confirmar y cancelar cambian el estado del pedido', function () {
    [$crear, $confirmar, $cancelar, $productoRepo, $movimientoRepo] = contextoTienda();

    $queso = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Queso fresco',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'pieza',
        precioReferencia: 20,
        stockMinimo: null,
    ));
    darStock($movimientoRepo, $queso->id, 10);

    $pedido = $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-14',
        observaciones: null,
        items: [new ItemCarritoData(productoId: $queso->id, cantidad: 1)],
    ));

    $confirmado = $confirmar->ejecutar($pedido->id);
    expect($confirmado->estado)->toBe('confirmado');

    expect(fn () => $cancelar->ejecutar($pedido->id))->toThrow(PedidoYaRevisadoException::class);
});
