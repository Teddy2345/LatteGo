<?php

declare(strict_types=1);

namespace App\Domain\Acopio\ValueObjects;

use App\Domain\Acopio\Exceptions\UbicacionInvalidaException;
use DateTimeImmutable;

/**
 * Punto GPS donde el acopiador midio la leche. Da trazabilidad de que la
 * recepcion ocurrio en la zona declarada y no se digito desde otro lugar.
 */
final readonly class UbicacionCaptura
{
    /**
     * Una lectura con mas de 100 m de error no permite distinguir entre zonas
     * de acopio vecinas, asi que no sirve como evidencia de trazabilidad.
     */
    public const PRECISION_MAXIMA_METROS = 100.0;

    public function __construct(
        public float $latitud,
        public float $longitud,
        public float $precisionMetros,
        public DateTimeImmutable $capturadoEn,
    ) {
        if ($latitud < -90 || $latitud > 90) {
            throw new UbicacionInvalidaException('La latitud debe estar entre -90 y 90 grados.');
        }

        if ($longitud < -180 || $longitud > 180) {
            throw new UbicacionInvalidaException('La longitud debe estar entre -180 y 180 grados.');
        }

        if ($precisionMetros <= 0) {
            throw new UbicacionInvalidaException('La precision del GPS debe ser mayor a cero metros.');
        }

        if ($precisionMetros > self::PRECISION_MAXIMA_METROS) {
            throw new UbicacionInvalidaException(
                'La precision del GPS supera los '.self::PRECISION_MAXIMA_METROS.' metros permitidos.'
            );
        }
    }
}
