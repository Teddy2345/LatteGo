<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

enum PruebaAlcohol: string
{
    case Aceptada = 'aceptada';
    case Rechazada = 'rechazada';
}
