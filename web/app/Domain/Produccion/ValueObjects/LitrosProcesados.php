<?php

declare(strict_types=1);

namespace App\Domain\Produccion\ValueObjects;

use App\Domain\Produccion\Exceptions\LitrosProcesadosInvalidosException;

final readonly class LitrosProcesados
{
    public float $valor;

    public function __construct(float $valor)
    {
        if ($valor <= 0.0) {
            throw new LitrosProcesadosInvalidosException('Los litros procesados deben ser mayores a 0.');
        }

        $this->valor = round($valor, 2);
    }
}
