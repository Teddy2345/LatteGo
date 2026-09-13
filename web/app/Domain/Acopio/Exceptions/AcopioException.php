<?php

declare(strict_types=1);

namespace App\Domain\Acopio\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class AcopioException extends DomainException implements DomainRuleException
{
}
