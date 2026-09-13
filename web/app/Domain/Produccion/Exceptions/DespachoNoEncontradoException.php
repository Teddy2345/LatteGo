<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class DespachoNoEncontradoException extends ProduccionException implements NotFoundException
{
}
