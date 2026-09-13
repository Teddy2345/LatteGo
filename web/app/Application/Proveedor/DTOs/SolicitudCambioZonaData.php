<?php

declare(strict_types=1);

namespace App\Application\Proveedor\DTOs;

final readonly class SolicitudCambioZonaData
{
    public function __construct(
        public int $id,
        public int $proveedorId,
        public string $proveedorNombre,
        public ?string $rutaActualNombre,
        public string $rutaSolicitadaNombre,
        public string $fechaCambio,
        public ?string $motivo,
        public string $estado,
        public ?string $solicitadoPorNombre,
        public ?string $fechaRevision,
        public ?string $observacionRevision,
    ) {
    }
}
