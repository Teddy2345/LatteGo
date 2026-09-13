<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\Exceptions;

use App\Domain\Shared\Exceptions\DomainRuleException;
use DomainException;

abstract class PedidoException extends DomainException implements DomainRuleException
{
}
