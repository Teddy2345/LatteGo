<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Pedidos\Entities\Pedido;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;

final class ListarPedidosUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
    ) {
    }

    /**
     * @return PedidoData[]
     */
    public function ejecutar(?EstadoPedido $estado = null, ?int $repartidorId = null): array
    {
        $pedidos = match (true) {
            $repartidorId !== null => $this->pedidos->listarPorRepartidor($repartidorId),
            $estado !== null => $this->pedidos->listarPorEstado($estado),
            default => $this->pedidos->listarTodos(),
        };

        if ($repartidorId !== null && $estado !== null) {
            $pedidos = array_values(array_filter($pedidos, static fn (Pedido $p): bool => $p->estado === $estado));
        }

        return array_map(
            static fn (Pedido $pedido): PedidoData => PedidoData::desdeEntidad($pedido),
            $pedidos,
        );
    }
}
