<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Exceptions;

/**
 * Se pidio trasladar al proveedor a la zona en la que ya esta: no hay
 * cambio que aprobar.
 */
final class ZonaSolicitadaIgualException extends SolicitudCambioZonaException
{
}
