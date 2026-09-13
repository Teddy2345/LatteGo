<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class ProduccionNoEncontradaException extends ProduccionException implements NotFoundException
{
}
