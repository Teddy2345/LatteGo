<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

/**
 * Lo que un motor de OCR extrajo de la foto del equipo. Ningun valor se
 * guarda como analisis todavia: el usuario los revisa y confirma primero
 * (ver flujo en CalidadFormulario).
 */
final readonly class LecturaOcr
{
    /**
     * @param  array<string, float>  $valores  Claves posibles: grasa, proteina,
     *                                          lactosa, densidad, temperatura,
     *                                          ph, agua_agregada.
     */
    public function __construct(
        public array $valores,
        public float $confianza,
    ) {
    }

    public function sinValoresReconocidos(): bool
    {
        return $this->valores === [];
    }
}
