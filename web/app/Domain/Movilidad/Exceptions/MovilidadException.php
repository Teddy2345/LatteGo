<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class MovilidadException extends DomainException implements DomainRuleException
{
}
