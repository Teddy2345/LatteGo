<?php

declare(strict_types=1);

namespace App\Domain\Pagos\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class PagoException extends DomainException implements DomainRuleException
{
}
