<?php

declare(strict_types=1);

namespace App\Domain\Acopio\ValueObjects;

use App\Domain\Acopio\Exceptions\CantidadLitrosInvalidaException;

final readonly class CantidadLitros
{
    public float $valor;

    public function __construct(float $valor)
    {
        if ($valor <= 0.0) {
            throw new CantidadLitrosInvalidaException('La cantidad de litros debe ser mayor a 0.');
        }

        $this->valor = round($valor, 2);
    }
}
