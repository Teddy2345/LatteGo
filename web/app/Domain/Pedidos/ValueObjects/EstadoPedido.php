<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\ValueObjects;

enum EstadoPedido: string
{
    case Pendiente = 'pendiente';
    case Confirmado = 'confirmado';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';
}
