<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Entities;

use DateTimeImmutable;

/**
 * El acopiador visito al proveedor y no se llevo leche (proveedor ausente,
 * sin produccion ese dia, etc.). Vive fuera de Acopio porque no hay litros
 * que registrar: es un hecho del recorrido, no de la recepcion de leche.
 */
final readonly class IncidenciaRecorrido
{
    public function __construct(
        public ?int $id,
        public int $proveedorId,
        public ?int $movilidadId,
        public DateTimeImmutable $fecha,
        public ?string $motivo,
        public ?int $registradoPor,
    ) {
    }
}
