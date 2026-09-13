<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\ValueObjects;

use App\Domain\Proveedor\Exceptions\PrecioLitroInvalidoException;
use Stringable;

final readonly class PrecioLitro implements Stringable
{
    public float $valor;

    public function __construct(float $valor)
    {
        if ($valor <= 0.0) {
            throw new PrecioLitroInvalidoException('El precio por litro debe ser mayor a 0.');
        }

        $this->valor = round($valor, 2);
    }

    public function __toString(): string
    {
        return number_format($this->valor, 2, '.', '');
    }
}
