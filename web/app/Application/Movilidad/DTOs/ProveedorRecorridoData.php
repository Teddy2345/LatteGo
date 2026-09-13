<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

final readonly class ProveedorRecorridoData
{
    public function __construct(
        public int $proveedorId,
        public string $nombre,
        public ?string $finca,
        /** pendiente | recogido | no_entrego */
        public string $estado,
        public ?float $litros,
        public ?string $horaRegistro,
        public bool $cambioZonaPendiente = false,
        public ?string $zonaSolicitada = null,
    ) {
    }
}
