<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\ValueObjects;

use App\Domain\Proveedor\Exceptions\LitrosPromedioInvalidoException;

final readonly class LitrosPromedio
{
    public int $valor;

    public function __construct(int $valor)
    {
        if ($valor < 0) {
            throw new LitrosPromedioInvalidoException('Los litros promedio no pueden ser negativos.');
        }

        $this->valor = $valor;
    }
}
