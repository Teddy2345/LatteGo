<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontradoException;
use App\Domain\Pedidos\Repositories\PedidoRepository;

final class CancelarPedidoUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
    ) {
    }

    public function ejecutar(int $id): PedidoData
    {
        $pedido = $this->pedidos->buscarPorId($id);

        if ($pedido === null) {
            throw new PedidoNoEncontradoException("No existe un pedido con id {$id}.");
        }

        return PedidoData::desdeEntidad($this->pedidos->guardar($pedido->cancelar()));
    }
}
