<?php

declare(strict_types=1);

namespace App\Application\Calidad\DTOs;

final readonly class RegistrarCalidadData
{
    public function __construct(
        public int $proveedorId,
        public int $acopioId,
        public string $fecha,
        public float $temperatura,
        public float $grasa,
        public float $solidosNoGrasos,
        public float $densidad,
        public float $proteina,
        public float $lactosa,
        public float $sales,
        public float $aguaAgregada,
        public float $ph,
        public bool $pruebaAlcoholAceptada,
        public ?string $motivoRechazoManual,
        public ?string $fotoPath = null,
    ) {
    }
}
