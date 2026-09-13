<?php

declare(strict_types=1);

namespace App\Application\Produccion\DTOs;

use App\Domain\Produccion\Entities\Despacho;

final readonly class DespachoData
{
    public function __construct(
        public int $id,
        public int $produccionId,
        public ?int $despachadorId,
        public int $quesosRecibidos,
        public int $quesosDespachados,
        public int $merma,
        public ?string $observaciones,
    ) {
    }

    public static function desdeEntidad(Despacho $despacho): self
    {
        return new self(
            id: $despacho->id,
            produccionId: $despacho->produccionId,
            despachadorId: $despacho->despachadorId,
            quesosRecibidos: $despacho->quesosRecibidos,
            quesosDespachados: $despacho->quesosDespachados,
            merma: $despacho->merma,
            observaciones: $despacho->observaciones,
        );
    }
}
