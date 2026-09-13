<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\ValueObjects;

enum EstadoSolicitudCambioZona: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
}
