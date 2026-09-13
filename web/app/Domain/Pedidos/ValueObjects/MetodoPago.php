<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\ValueObjects;

enum MetodoPago: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Qr = 'qr';
}
