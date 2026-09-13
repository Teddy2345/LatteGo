<?php

declare(strict_types=1);

namespace App\Application\Produccion\DTOs;

final readonly class RegistrarDespachoData
{
    public function __construct(
        public int $produccionId,
        public ?int $despachadorId,
        public int $quesosRecibidos,
        public int $quesosDespachados,
        public ?string $observaciones,
    ) {
    }
}
