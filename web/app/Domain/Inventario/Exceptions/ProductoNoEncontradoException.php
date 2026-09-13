<?php

declare(strict_types=1);

namespace App\Domain\Inventario\Exceptions;

use App\Domain\Shared\Exceptions\NotFoundException;

final class ProductoNoEncontradoException extends InventarioException implements NotFoundException
{
}
