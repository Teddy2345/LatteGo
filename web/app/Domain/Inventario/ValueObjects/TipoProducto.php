<?php

declare(strict_types=1);

namespace App\Domain\Inventario\ValueObjects;

enum TipoProducto: string
{
    case ProductoTerminado = 'producto_terminado';
    case Insumo = 'insumo';
}
