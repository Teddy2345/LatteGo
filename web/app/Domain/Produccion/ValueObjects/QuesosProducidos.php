<?php

declare(strict_types=1);

namespace App\Domain\Produccion\ValueObjects;

use App\Domain\Produccion\Exceptions\QuesosProducidosInvalidosException;

final readonly class QuesosProducidos
{
    public int $valor;

    public function __construct(int $valor)
    {
        if ($valor < 0) {
            throw new QuesosProducidosInvalidosException('Los quesos producidos no pueden ser negativos.');
        }

        $this->valor = $valor;
    }
}
