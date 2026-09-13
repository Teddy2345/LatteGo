<?php

declare(strict_types=1);

namespace App\Application\Pedidos\DTOs;

final readonly class EntregarPedidoData
{
    public function __construct(
        public int $id,
        public string $metodoPago,
        public float $montoCobrado,
        public string $fechaEntrega,
    ) {
    }
}
