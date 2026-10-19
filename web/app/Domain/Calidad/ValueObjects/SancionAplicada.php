<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

/**
 * Castigo a un ganadero que añadio agua a la leche.
 */
enum SancionAplicada: string
{
    case Ninguna = 'ninguna';

    /** Primera vez: la entrega adulterada no se paga. */
    case SinPagoEntrega = 'sin_pago_entrega';

    /**
     * Reincidencia: no puede entregar el resto de esa semana de pago y no se
     * le paga nada de esa semana. La siguiente vuelve a la normalidad sola.
     */
    case SuspensionSemana = 'suspension_semana';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ninguna => 'Ninguna',
            self::SinPagoEntrega => 'Entrega sin pago',
            self::SuspensionSemana => 'Suspendido toda la semana sin pago',
        };
    }
}
