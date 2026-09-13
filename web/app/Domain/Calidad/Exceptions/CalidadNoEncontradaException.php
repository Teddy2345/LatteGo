<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class CalidadNoEncontradaException extends CalidadException implements NotFoundException
{
}
