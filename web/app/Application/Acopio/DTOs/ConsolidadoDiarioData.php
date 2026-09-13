<?php

declare(strict_types=1);

namespace App\Application\Acopio\DTOs;

final readonly class ConsolidadoDiarioData
{
    /**
     * @param  ConsolidadoZonaData[]  $zonas
     */
    public function __construct(
        public string $fecha,
        public float $totalLitros,
        public int $totalRegistros,
        public string $actualizadoEn,
        public array $zonas,
    ) {
    }
}
