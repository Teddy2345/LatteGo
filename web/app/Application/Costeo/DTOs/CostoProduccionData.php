<?php

declare(strict_types=1);

namespace App\Application\Costeo\DTOs;

final readonly class CostoProduccionData
{
    /**
     * @param  CostoInsumoData[]  $insumos
     */
    public function __construct(
        public int $produccionId,
        public string $fecha,
        public float $litrosProcesados,
        public int $quesosProducidos,
        public ?float $precioLechePromedioPonderado,
        public ?float $costoLeche,
        public array $insumos,
        public float $costoInsumosConocido,
        public bool $costoInsumosIncompleto,
        public ?float $costoTotal,
        public ?float $costoPorQueso,
    ) {
    }
}
