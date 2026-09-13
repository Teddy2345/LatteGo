<?php

declare(strict_types=1);

namespace App\Domain\Pedidos\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class PedidoNoEncontradoException extends PedidoException implements NotFoundException
{
}
