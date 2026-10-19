<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\ValueObjects;

enum MetodoPago: string
{
    case Efectivo = 'efectivo';
    case Transferencia = 'transferencia';
    case Qr = 'qr';
    case Yape = 'yape';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Transferencia => 'Transferencia',
            self::Qr => 'QR',
            self::Yape => 'Yape',
        };
    }

    /**
     * Todo lo que no es efectivo deja un numero de operacion. Guardarlo es
     * lo unico que permite cotejar el cobro contra el estado de cuenta: una
     * captura de pantalla no prueba que el dinero llego.
     */
    public function exigeReferencia(): bool
    {
        return $this !== self::Efectivo;
    }
}
