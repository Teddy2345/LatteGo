<?php

declare(strict_types=1);

namespace App\Application\Acopio\DTOs;

final readonly class ConsolidadoZonaData
{
    public function __construct(
        public ?int $rutaId,
        public string $zona,
        public float $litros,
        public int $registros,
    ) {
    }
}
