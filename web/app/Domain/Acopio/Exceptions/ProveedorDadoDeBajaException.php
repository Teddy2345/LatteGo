<?php

declare(strict_types=1);

namespace App\Domain\Acopio\Exceptions;

/**
 * A quien esta dado de baja no se le recibe leche: para eso se le dio de
 * baja. Si vuelve a entregar, primero se le reactiva la ficha.
 */
final class ProveedorDadoDeBajaException extends AcopioException
{
}
