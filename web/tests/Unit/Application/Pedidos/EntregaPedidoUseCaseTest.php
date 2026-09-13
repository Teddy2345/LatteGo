<?php

declare(strict_types=1);

use App\Application\Inventario\DTOs\RegistrarProductoData;
use App\Application\Inventario\UseCases\RegistrarProductoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoData;
use App\Application\Pedidos\DTOs\EntregarPedidoData;
use App\Application\Pedidos\DTOs\ItemCarritoData;
use App\Application\Pedidos\UseCases\AsignarRepartidorUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\EntregarPedidoUseCase;
use App\Domain\Inventario\Entities\MovimientoInventario;
use App\Domain\Inventario\ValueObjects\CantidadProducto;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalidoException;
use Tests\Support\InMemoryMovimientoInventarioRepository;
use Tests\Support\InMemoryPedidoRepository;
use Tests\Support\InMemoryProductoRepository;

function crearPedidoConfirmadoDePrueba(): array
{
    $productoRepo = new InMemoryProductoRepository();
    $movimientoRepo = new InMemoryMovimientoInventarioRepository();
    $pedidoRepo = new InMemoryPedidoRepository();

    $crear = new CrearPedidoUseCase($pedidoRepo, $productoRepo, $movimientoRepo);
    $confirmar = new ConfirmarPedidoUseCase($pedidoRepo);
    $asignar = new AsignarRepartidorUseCase($pedidoRepo);
    $entregar = new EntregarPedidoUseCase($pedidoRepo);

    $queso = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Queso fresco',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'pieza',
        precioReferencia: 20,
        stockMinimo: null,
    ));
    $movimientoRepo->guardar(MovimientoInventario::compra(
        productoId: $queso->id,
        fecha: new DateTimeImmutable('2026-09-01'),
        cantidad: new CantidadProducto(10),
        lote: null,
        observaciones: null,
        usuarioId: null,
    ));

    $pedido = $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-15',
        observaciones: null,
        items: [new ItemCarritoData(productoId: $queso->id, cantidad: 2)],
    ));
    $confirmado = $confirmar->ejecutar($pedido->id);

    return [$asignar, $entregar, $confirmado];
}

test('no se puede entregar un pedido sin repartidor asignado', function () {
    [, $entregar, $pedido] = crearPedidoConfirmadoDePrueba();

    expect(fn () => $entregar->ejecutar(new EntregarPedidoData(
        id: $pedido->id,
        metodoPago: 'efectivo',
        montoCobrado: 40,
        fechaEntrega: '2026-09-15',
    )))->toThrow(EstadoPedidoInvalidoException::class);
});

test('asignar un repartidor y entregar deja constancia del cobro', function () {
    [$asignar, $entregar, $pedido] = crearPedidoConfirmadoDePrueba();

    $asignar->ejecutar($pedido->id, 7);
    $entregado = $entregar->ejecutar(new EntregarPedidoData(
        id: $pedido->id,
        metodoPago: 'transferencia',
        montoCobrado: 40,
        fechaEntrega: '2026-09-15',
    ));

    expect($entregado->estado)->toBe('entregado')
        ->and($entregado->repartidorId)->toBe(7)
        ->and($entregado->metodoPago)->toBe('transferencia')
        ->and($entregado->montoCobrado)->toBe(40.0)
        ->and($entregado->fechaEntrega)->toBe('2026-09-15');
});

test('no se puede asignar repartidor a un pedido que no esta confirmado', function () {
    $productoRepo = new InMemoryProductoRepository();
    $movimientoRepo = new InMemoryMovimientoInventarioRepository();
    $pedidoRepo = new InMemoryPedidoRepository();
    $crear = new CrearPedidoUseCase($pedidoRepo, $productoRepo, $movimientoRepo);
    $asignar = new AsignarRepartidorUseCase($pedidoRepo);

    $queso = (new RegistrarProductoUseCase($productoRepo))->ejecutar(new RegistrarProductoData(
        nombre: 'Queso fresco',
        tipo: 'producto_terminado',
        categoria: null,
        unidad: 'pieza',
        precioReferencia: 20,
        stockMinimo: null,
    ));
    $movimientoRepo->guardar(MovimientoInventario::compra(
        productoId: $queso->id,
        fecha: new DateTimeImmutable('2026-09-01'),
        cantidad: new CantidadProducto(10),
        lote: null,
        observaciones: null,
        usuarioId: null,
    ));

    $pedido = $crear->ejecutar(new CrearPedidoData(
        clienteNombre: 'Maria Perez',
        clienteTelefono: '71234567',
        clienteDireccion: 'Calle Sucre 123',
        fecha: '2026-09-15',
        observaciones: null,
        items: [new ItemCarritoData(productoId: $queso->id, cantidad: 2)],
    ));

    expect(fn () => $asignar->ejecutar($pedido->id, 7))->toThrow(EstadoPedidoInvalidoException::class);
});

test('no se puede entregar un pedido dos veces', function () {
    [$asignar, $entregar, $pedido] = crearPedidoConfirmadoDePrueba();

    $asignar->ejecutar($pedido->id, 7);
    $entregar->ejecutar(new EntregarPedidoData(id: $pedido->id, metodoPago: 'efectivo', montoCobrado: 40, fechaEntrega: '2026-09-15'));

    expect(fn () => $entregar->ejecutar(new EntregarPedidoData(
        id: $pedido->id,
        metodoPago: 'efectivo',
        montoCobrado: 40,
        fechaEntrega: '2026-09-15',
    )))->toThrow(EstadoPedidoInvalidoException::class);
});
