<?php

declare(strict_types=1);

namespace App\Domain\Produccion\Entities;

use App\Domain\Produccion\ValueObjects\LitrosProcesados;
use App\Domain\Produccion\ValueObjects\QuesosProducidos;
use App\Domain\Produccion\ValueObjects\RendimientoPorcentaje;
use DateTimeImmutable;

final class Produccion
{
    private function __construct(
        public readonly ?int $id,
        public readonly DateTimeImmutable $fecha,
        public readonly LitrosProcesados $litrosProcesados,
        public readonly QuesosProducidos $quesosProducidos,
        public readonly RendimientoPorcentaje $rendimiento,
        public readonly ?int $jefaProduccionId,
        public readonly ?string $observaciones,
        public readonly ?int $productoId,
    ) {
    }

    /**
     * El rendimiento (quesos por cada 100 litros) se calcula, no se
     * ingresa: evita inconsistencias entre los tres valores.
     */
    public static function crear(
        DateTimeImmutable $fecha,
        LitrosProcesados $litrosProcesados,
        QuesosProducidos $quesosProducidos,
        ?int $jefaProduccionId,
        ?string $observaciones,
        ?int $productoId = null,
    ): self {
        return new self(
            id: null,
            fecha: $fecha,
            litrosProcesados: $litrosProcesados,
            quesosProducidos: $quesosProducidos,
            rendimiento: RendimientoPorcentaje::calcular($litrosProcesados, $quesosProducidos),
            jefaProduccionId: $jefaProduccionId,
            observaciones: $observaciones,
            productoId: $productoId,
        );
    }

    public static function reconstituir(
        int $id,
        DateTimeImmutable $fecha,
        LitrosProcesados $litrosProcesados,
        QuesosProducidos $quesosProducidos,
        RendimientoPorcentaje $rendimiento,
        ?int $jefaProduccionId,
        ?string $observaciones,
        ?int $productoId = null,
    ): self {
        return new self($id, $fecha, $litrosProcesados, $quesosProducidos, $rendimiento, $jefaProduccionId, $observaciones, $productoId);
    }
}
