<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

final readonly class RegistrarNoEntregaData
{
    public function __construct(
        public int $proveedorId,
        public ?int $movilidadId,
        public string $fecha,
        public ?string $motivo,
        public ?int $registradoPor,
    ) {
    }
}
