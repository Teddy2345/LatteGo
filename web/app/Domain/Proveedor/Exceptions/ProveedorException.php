<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class ProveedorException extends DomainException implements DomainRuleException
{
}
