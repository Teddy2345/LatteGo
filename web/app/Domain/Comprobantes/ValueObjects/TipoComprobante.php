<?php

declare(strict_types=1);

namespace App\Domain\Comprobantes\ValueObjects;

enum TipoComprobante: string
{
    case Boleta = 'boleta';
    case Factura = 'factura';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Boleta => 'Boleta de venta',
            self::Factura => 'Factura',
        };
    }

    /** Cada tipo lleva su propia numeracion, como en cualquier talonario. */
    public function serie(): string
    {
        return match ($this) {
            self::Boleta => 'B001',
            self::Factura => 'F001',
        };
    }

    /** La factura va a nombre de una empresa: sin RUC no se emite. */
    public function exigeDocumento(): bool
    {
        return $this === self::Factura;
    }

    public function etiquetaDocumento(): string
    {
        return $this === self::Factura ? 'RUC' : 'DNI';
    }

    public static function desde(string $valor): self
    {
        return self::tryFrom($valor) ?? self::Boleta;
    }
}
