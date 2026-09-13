<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\ValueObjects;

use App\Domain\Proveedor\Exceptions\CedulaInvalidaException;
use Stringable;

final readonly class Cedula implements Stringable
{
    public string $valor;

    public function __construct(string $valor)
    {
        $valor = trim($valor);

        if (mb_strlen($valor) < 6) {
            throw new CedulaInvalidaException('La cedula debe tener al menos 6 caracteres.');
        }

        $this->valor = $valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }

    public function equals(self $otra): bool
    {
        return $this->valor === $otra->valor;
    }
}
