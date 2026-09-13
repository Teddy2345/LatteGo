<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Pedidos\Entities\Pedido;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;

final class InMemoryPedidoRepository implements PedidoRepository
{
    /** @var array<int, Pedido> */
    private array $pedidos = [];

    private int $siguienteId = 1;

    public function guardar(Pedido $pedido): Pedido
    {
        $guardado = $pedido->id === null
            ? Pedido::reconstituir(
                id: $this->siguienteId++,
                clienteNombre: $pedido->clienteNombre,
                clienteTelefono: $pedido->clienteTelefono,
                clienteDireccion: $pedido->clienteDireccion,
                fecha: $pedido->fecha,
                estado: $pedido->estado,
                observaciones: $pedido->observaciones,
                items: $pedido->items,
                repartidorId: $pedido->repartidorId,
                metodoPago: $pedido->metodoPago,
                montoCobrado: $pedido->montoCobrado,
                fechaEntrega: $pedido->fechaEntrega,
            )
            : $pedido;

        $this->pedidos[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Pedido
    {
        return $this->pedidos[$id] ?? null;
    }

    public function listarTodos(): array
    {
        return array_values($this->pedidos);
    }

    public function listarPorEstado(EstadoPedido $estado): array
    {
        return array_values(array_filter(
            $this->pedidos,
            static fn (Pedido $p): bool => $p->estado === $estado,
        ));
    }

    public function listarPorRepartidor(int $repartidorId): array
    {
        return array_values(array_filter(
            $this->pedidos,
            static fn (Pedido $p): bool => $p->repartidorId === $repartidorId,
        ));
    }
}
