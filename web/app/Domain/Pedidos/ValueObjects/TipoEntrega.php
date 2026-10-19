<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Como recibe su pedido quien lo hizo desde la app.
 *
 * El proveedor que baja a dejar su leche suele preferir llevarselo de la
 * planta en ese mismo viaje; el resto pide que se lo lleven.
 */
enum TipoEntrega: string
{
    case Delivery = 'delivery';
    case Recojo = 'recojo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Delivery => 'Que me lo lleven',
            self::Recojo => 'Lo recojo en planta',
        };
    }

    public static function desde(?string $valor): ?self
    {
        return $valor === null ? null : self::tryFrom($valor);
    }
}
