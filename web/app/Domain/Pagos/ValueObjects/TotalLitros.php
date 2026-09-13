<?php

declare(strict_types=1);

namespace App\Domain\Pagos\ValueObjects;

use App\Domain\Pagos\Exceptions\TotalLitrosInvalidoException;

final readonly class TotalLitros
{
    public float $valor;

    public function __construct(float $valor)
    {
        if ($valor < 0.0) {
            throw new TotalLitrosInvalidoException('El total de litros no puede ser negativo.');
        }

        $this->valor = round($valor, 2);
    }
}
