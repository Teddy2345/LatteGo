<?php

declare(strict_types=1);

namespace App\Application\Acopio\DTOs;

final readonly class RegistrarAcopioData
{
    public function __construct(
        public int $proveedorId,
        public ?int $acopiadorId,
        public ?int $rutaId,
        public string $fecha,
        public float $cantidadLitros,
        public ?string $observaciones,
        public ?float $perdidaLitros,
        public ?string $motivoPerdida,
        public ?int $movilidadId = null,
    ) {
    }
}
