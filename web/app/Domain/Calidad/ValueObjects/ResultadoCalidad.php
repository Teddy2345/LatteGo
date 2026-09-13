<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

enum ResultadoCalidad: string
{
    case Aceptada = 'aceptada';
    case Rechazada = 'rechazada';
}
