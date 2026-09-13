<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class ProduccionException extends DomainException implements DomainRuleException
{
}
