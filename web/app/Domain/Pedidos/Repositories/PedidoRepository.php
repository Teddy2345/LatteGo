<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Entities\Pedido;
use App\Domain\Pedidos\ValueObjects\EstadoPedido;

interface PedidoRepository
{
    public function guardar(Pedido $pedido): Pedido;

    public function buscarPorId(int $id): ?Pedido;

    /**
     * @return Pedido[]
     */
    public function listarTodos(): array;

    /**
     * @return Pedido[]
     */
    public function listarPorEstado(EstadoPedido $estado): array;

    /**
     * Pedidos asignados a ese repartidor, para su propia bandeja de reparto.
     *
     * @return Pedido[]
     */
    public function listarPorRepartidor(int $repartidorId): array;
}
