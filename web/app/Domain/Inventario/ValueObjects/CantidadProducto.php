<?php

declare(strict_types=1);

namespace App\Domain\Inventario\ValueObjects;

use App\Domain\Inventario\Exceptions\CantidadProductoInvalidaException;

final readonly class CantidadProducto
{
    public float $valor;

    public function __construct(float $valor)
    {
        if (! is_finite($valor) || $valor <= 0) {
            throw new CantidadProductoInvalidaException('La cantidad debe ser mayor a cero.');
        }

        $this->valor = round($valor, 2);
    }
}
