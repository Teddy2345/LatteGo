<?php

declare(strict_types=1);

namespace App\Application\Produccion\DTOs;

use App\Domain\Produccion\Entities\Produccion;

final readonly class ProduccionData
{
    public function __construct(
        public int $id,
        public string $fecha,
        public float $litrosProcesados,
        public int $quesosProducidos,
        public float $rendimientoPorcentaje,
        public bool $rendimientoEnRangoEsperado,
        public ?int $jefaProduccionId,
        public ?string $observaciones,
        public ?int $productoId = null,
    ) {
    }

    public static function desdeEntidad(Produccion $produccion): self
    {
        return new self(
            id: $produccion->id,
            fecha: $produccion->fecha->format('Y-m-d'),
            litrosProcesados: $produccion->litrosProcesados->valor,
            quesosProducidos: $produccion->quesosProducidos->valor,
            rendimientoPorcentaje: $produccion->rendimiento->valor,
            rendimientoEnRangoEsperado: $produccion->rendimiento->dentroDelRangoEsperado(),
            jefaProduccionId: $produccion->jefaProduccionId,
            observaciones: $produccion->observaciones,
            productoId: $produccion->productoId,
        );
    }
}
