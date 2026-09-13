<?php

declare(strict_types=1);

namespace App\Application\Acopio\DTOs;

final readonly class RegistrarAcopioMovilData
{
    public function __construct(
        public string $requestId,
        public int $proveedorId,
        public ?int $acopiadorId,
        public ?int $rutaId,
        public string $fecha,
        public float $cantidadLitros,
        public ?string $observaciones,
        public ?float $perdidaLitros,
        public ?string $motivoPerdida,
        public float $latitud,
        public float $longitud,
        public float $precisionMetros,
        public string $capturadoEn,
    ) {
    }
}
