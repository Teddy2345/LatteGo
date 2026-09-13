<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

enum MotivoRechazo: string
{
    case Adulteracion = 'adulteracion';
    case Acidez = 'acidez';
    case Otro = 'otro';
}
