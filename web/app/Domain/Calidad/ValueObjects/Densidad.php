<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

final readonly class Densidad
{
    public float $valor;

    public function __construct(float $valor)
    {
        $this->valor = $valor;
    }

    /**
     * Clasificacion segun el lactodensimetro: >=30 buena, 28-30 regular,
     * por debajo de 28 indica agua añadida.
     */
    public function clasificacion(): ClasificacionDensidad
    {
        return match (true) {
            $this->valor >= 30.0 => ClasificacionDensidad::Buena,
            $this->valor >= 28.0 => ClasificacionDensidad::Regular,
            default => ClasificacionDensidad::AguaAnadida,
        };
    }
}
