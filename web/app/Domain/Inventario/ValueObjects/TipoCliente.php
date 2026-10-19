<?php

declare(strict_types=1);

namespace App\Domain\Inventario\ValueObjects;

/**
 * A quien se le vende, que es lo que decide el precio.
 *
 * El precio que muestra la tienda es el de fuera del distrito. Al vecino de
 * Huata y al proveedor que entrega su leche se les cobra el suyo, y por eso
 * la venta guarda con cual de los tres se cobro: de otro modo un precio mas
 * bajo en el historial pareceria un descuento suelto del vendedor.
 */
enum TipoCliente: string
{
    case Externo = 'externo';
    case Vecino = 'vecino';
    case Proveedor = 'proveedor';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Externo => 'De fuera del distrito',
            self::Vecino => 'Vecino de Huata',
            self::Proveedor => 'Proveedor de la planta',
        };
    }

    /** Texto corto para las tablas del historial. */
    public function etiquetaCorta(): string
    {
        return match ($this) {
            self::Externo => 'Externo',
            self::Vecino => 'Vecino',
            self::Proveedor => 'Proveedor',
        };
    }

    /** Las ventas anteriores a los precios diferenciados se leen como externas. */
    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::Externo;
    }
}
