<?php

declare(strict_types=1);

namespace App\Application\Calidad\DTOs;

use App\Domain\Calidad\Entities\Calidad;

final readonly class CalidadData
{
    public function __construct(
        public int $id,
        public int $proveedorId,
        public int $acopioId,
        public string $fecha,
        public float $temperatura,
        public float $grasa,
        public float $solidosNoGrasos,
        public float $densidad,
        public string $densidadClasificacion,
        public float $proteina,
        public float $lactosa,
        public float $sales,
        public float $aguaAgregada,
        public float $ph,
        public string $pruebaAlcohol,
        public string $resultado,
        public ?string $motivoRechazo,
        public string $sancionAplicada,
        public ?string $fotoPath = null,
    ) {
    }

    public static function desdeEntidad(Calidad $calidad): self
    {
        return new self(
            id: $calidad->id,
            proveedorId: $calidad->proveedorId,
            acopioId: $calidad->acopioId,
            fecha: $calidad->fecha->format('Y-m-d'),
            temperatura: $calidad->temperatura,
            grasa: $calidad->grasa,
            solidosNoGrasos: $calidad->solidosNoGrasos,
            densidad: $calidad->densidad->valor,
            densidadClasificacion: $calidad->densidad->clasificacion()->value,
            proteina: $calidad->proteina,
            lactosa: $calidad->lactosa,
            sales: $calidad->sales,
            aguaAgregada: $calidad->aguaAgregada->valor,
            ph: $calidad->ph->valor,
            pruebaAlcohol: $calidad->pruebaAlcohol->value,
            resultado: $calidad->resultado->value,
            motivoRechazo: $calidad->motivoRechazo?->value,
            sancionAplicada: $calidad->sancionAplicada->value,
            fotoPath: $calidad->fotoPath,
        );
    }
}
