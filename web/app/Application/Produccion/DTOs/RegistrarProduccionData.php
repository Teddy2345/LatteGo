<?php

declare(strict_types=1);

namespace App\Application\Produccion\DTOs;

final readonly class RegistrarProduccionData
{
    public function __construct(
        public string $fecha,
        public float $litrosProcesados,
        public int $quesosProducidos,
        public ?int $jefaProduccionId,
        public ?string $observaciones,
        public ?int $productoId = null,
        /** @var InsumoUtilizadoData[] */
        public array $insumosUtilizados = [],
    ) {
    }
}
