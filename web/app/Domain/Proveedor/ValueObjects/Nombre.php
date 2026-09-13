<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\ValueObjects;

use App\Domain\Proveedor\Exceptions\NombreInvalidoException;
use Stringable;

final readonly class Nombre implements Stringable
{
    public string $valor;

    public function __construct(string $valor)
    {
        $valor = trim($valor);

        if ($valor === '') {
            throw new NombreInvalidoException('El nombre es requerido.');
        }

        if (mb_strlen($valor) > 120) {
            throw new NombreInvalidoException('El nombre no puede superar los 120 caracteres.');
        }

        $this->valor = $valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
