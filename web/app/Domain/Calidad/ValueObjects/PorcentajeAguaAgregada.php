<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

use App\Domain\Calidad\Exceptions\ValorInvalidoException;

final readonly class PorcentajeAguaAgregada
{
    public float $valor;

    public function __construct(float $valor)
    {
        if ($valor < 0.0) {
            throw new ValorInvalidoException('El porcentaje de agua añadida no puede ser negativo.');
        }

        $this->valor = $valor;
    }
}
