<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Exceptions;

/**
 * Se intento quitar un camion que ya tiene acopios registrados. La base de
 * datos lo impide (acopios.movilidad_id es restrictOnDelete); este error
 * traduce esa restriccion a un mensaje claro para el usuario.
 */
final class MovilidadConHistorialException extends MovilidadException
{
}
