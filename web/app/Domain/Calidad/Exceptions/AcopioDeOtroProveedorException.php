<?php

declare(strict_types=1);

namespace App\Domain\Calidad\Exceptions;

/**
 * El acopio analizado no es del proveedor indicado. Aceptarlo cargaria la
 * sancion por agua a un ganadero y le quitaria el pago de la entrega a otro.
 */
final class AcopioDeOtroProveedorException extends CalidadException
{
}
