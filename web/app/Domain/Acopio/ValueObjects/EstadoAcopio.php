<?php

declare(strict_types=1);

namespace App\Domain\Acopio\ValueObjects;

enum EstadoAcopio: string
{
    case PendienteSincronizar = 'pendiente_sincronizar';
    case Sincronizado = 'sincronizado';
}
