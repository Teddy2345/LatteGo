<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class InventarioException extends DomainException implements DomainRuleException
{
}
