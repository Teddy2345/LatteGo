<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class SolicitudCambioZonaException extends DomainException implements DomainRuleException
{
}
