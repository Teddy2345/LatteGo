<?php

declare(strict_types=1);

namespace App\Domain\Produccion\ValueObjects;

use App\Domain\Produccion\Exceptions\LitrosProcesadosInvalidosException;

final readonly class LitrosProcesados
{
    public float $valor;

    public function __construct(float $valor, bool $permitirCero = false)
    {
        // Un lote que solo usa insumos de Almacen no procesa leche de acopio.
        if (!is_finite($valor) || $valor < 0.0 || (!$permitirCero && $valor == 0.0)) {
            throw new LitrosProcesadosInvalidosException('Los litros procesados deben ser mayores a 0.');
        }

        $this->valor = round($valor, 2);
    }
}
