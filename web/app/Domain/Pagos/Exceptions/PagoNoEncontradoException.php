<?php

declare(strict_types=1);

namespace App\Domain\Pagos\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class PagoNoEncontradoException extends PagoException implements NotFoundException
{
}
