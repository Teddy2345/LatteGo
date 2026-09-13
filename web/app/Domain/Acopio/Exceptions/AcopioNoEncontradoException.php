<?php

declare(strict_types=1);

namespace App\Domain\Acopio\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class AcopioNoEncontradoException extends AcopioException implements NotFoundException
{
}
