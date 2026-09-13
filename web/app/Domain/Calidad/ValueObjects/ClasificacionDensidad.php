<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

enum ClasificacionDensidad: string
{
    case Buena = 'buena';
    case Regular = 'regular';
    case AguaAnadida = 'agua_anadida';
}
