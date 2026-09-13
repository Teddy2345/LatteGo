<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class SolicitudCambioZonaNoEncontradaException extends SolicitudCambioZonaException implements NotFoundException
{
}
