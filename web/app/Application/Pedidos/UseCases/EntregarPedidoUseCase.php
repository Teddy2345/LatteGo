<?php

declare(strict_types=1);

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\EntregarPedidoData;
use App\Application\Pedidos\DTOs\PedidoData;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontradoException;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\MetodoPago;
use DateTimeImmutable;

final class EntregarPedidoUseCase
{
    public function __construct(
        private readonly PedidoRepository $pedidos,
    ) {
    }

    public function ejecutar(EntregarPedidoData $datos): PedidoData
    {
        $pedido = $this->pedidos->buscarPorId($datos->id);

        if ($pedido === null) {
            throw new PedidoNoEncontradoException("No existe un pedido con id {$datos->id}.");
        }

        $entregado = $pedido->entregar(
            metodoPago: MetodoPago::from($datos->metodoPago),
            montoCobrado: $datos->montoCobrado,
            fechaEntrega: new DateTimeImmutable($datos->fechaEntrega),
        );

        return PedidoData::desdeEntidad($this->pedidos->guardar($entregado));
    }
}
