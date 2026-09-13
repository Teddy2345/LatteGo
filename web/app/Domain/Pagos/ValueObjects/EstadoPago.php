<?php

declare(strict_types=1);

namespace App\Domain\Pagos\ValueObjects;

enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
}
