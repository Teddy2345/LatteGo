<?php

declare(strict_types=1);

namespace App\Domain\Notificacion\ValueObjects;

enum NivelNotificacion: string
{
    case Info = 'info';
    case Advertencia = 'advertencia';
    case Alerta = 'alerta';
}
