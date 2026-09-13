<?php

declare(strict_types=1);

namespace App\Application\Produccion\DTOs;

final readonly class AptitudPasteurizadaData
{
    public function __construct(
        public int $proveedorId,
        public bool $apto,
        public string $motivo,
    ) {
    }
}
