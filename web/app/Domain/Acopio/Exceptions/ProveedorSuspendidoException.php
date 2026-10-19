<?php

declare(strict_types=1);

namespace App\Domain\Acopio\Exceptions;

/**
 * El proveedor reincidio añadiendo agua a la leche y esta suspendido: no
 * puede entregar hasta que termine esa semana de pago.
 */
final class ProveedorSuspendidoException extends AcopioException
{
}
