<?php

declare(strict_types=1);

namespace App\Domain\Calidad\ValueObjects;

use App\Domain\Calidad\Exceptions\ValorInvalidoException;

final readonly class Ph
{
    public float $valor;

    public function __construct(float $valor)
    {
        if ($valor < 0.0 || $valor > 14.0) {
            throw new ValorInvalidoException('El pH debe estar entre 0 y 14.');
        }

        $this->valor = $valor;
    }
}
