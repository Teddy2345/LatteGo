<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Exceptions;

/**
 * El proveedor ya tiene una solicitud de cambio de zona pendiente de
 * revision; hay que resolverla antes de registrar otra.
 */
final class SolicitudDuplicadaException extends SolicitudCambioZonaException
{
}
