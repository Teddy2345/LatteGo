<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Exceptions;

/**
 * Algo impide operar con la cuenta de la app del proveedor: todavia no la
 * tiene, ya la tenia, esta dado de baja o le falta la cedula con la que
 * entra.
 */
final class CuentaProveedorException extends ProveedorException
{
}
