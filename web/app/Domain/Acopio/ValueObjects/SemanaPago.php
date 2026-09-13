<?php

declare(strict_types=1);

namespace App\Domain\Acopio\ValueObjects;

use DateTimeImmutable;

/**
 * La semana de acopio/pago corre de jueves a miercoles.
 */
final readonly class SemanaPago
{
    private function __construct(
        public DateTimeImmutable $inicio,
        public DateTimeImmutable $fin,
    ) {
    }

    public static function desde(DateTimeImmutable $fecha): self
    {
        $diaIso = (int) $fecha->format('N');
        $diasDesdeJueves = ($diaIso - 4 + 7) % 7;

        $inicio = $fecha->modify("-{$diasDesdeJueves} days")->setTime(0, 0);
        $fin = $inicio->modify('+6 days');

        return new self($inicio, $fin);
    }
}
