<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class CalidadException extends DomainException implements DomainRuleException
{
}
