<?php

declare(strict_types=1);

namespace App\Application\Proveedor\DTOs;

final readonly class CrearSolicitudCambioZonaData
{
    public function __construct(
        public int $proveedorId,
        public int $rutaSolicitadaId,
        public string $fechaCambio,
        public ?string $motivo,
        public int $solicitadoPor,
    ) {
    }
}
