<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class MovilidadNoEncontradaException extends MovilidadException implements NotFoundException
{
}
