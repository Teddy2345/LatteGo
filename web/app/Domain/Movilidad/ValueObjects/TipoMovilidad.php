<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\ValueObjects;

enum TipoMovilidad: string
{
    case Camion = 'camion';
    case Motocarga = 'motocarga';

    /** Proveedores que entregan la leche directamente en planta, sin vehiculo asignado. */
    case Planta = 'planta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Camion => 'Camión',
            self::Motocarga => 'Motocarga',
            self::Planta => 'Entrega directa en planta',
        };
    }
}
