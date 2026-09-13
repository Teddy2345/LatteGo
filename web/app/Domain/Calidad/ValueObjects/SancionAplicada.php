<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

enum SancionAplicada: string
{
    case Ninguna = 'ninguna';
    case DescuentoSemana = 'descuento_semana';
    case RetiroTemporal = 'retiro_temporal';
}
