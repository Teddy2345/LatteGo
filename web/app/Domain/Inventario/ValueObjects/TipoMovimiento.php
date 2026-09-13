<?php

declare(strict_types=1);

namespace App\Domain\Inventario\ValueObjects;

enum TipoMovimiento: string
{
    /** Transformacion de leche en producto terminado: entra al almacen. */
    case Produccion = 'produccion';

    /** Salida por comercializacion. */
    case Venta = 'venta';

    /** Compra de un insumo (sal, cuajo, envases...): entra al almacen. */
    case Compra = 'compra';

    /** Consumo de un insumo durante una produccion: sale del almacen. */
    case UsoProduccion = 'uso_produccion';

    /**
     * Signo con el que el movimiento afecta al stock del producto.
     */
    public function signo(): int
    {
        return match ($this) {
            self::Produccion, self::Compra => 1,
            self::Venta, self::UsoProduccion => -1,
        };
    }

    /**
     * @return self[] Tipos que suman al stock.
     */
    public static function entradas(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $t): bool => $t->signo() === 1));
    }
}
