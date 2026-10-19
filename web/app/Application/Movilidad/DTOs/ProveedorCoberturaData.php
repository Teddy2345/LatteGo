<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

final readonly class ProveedorCoberturaData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public ?string $finca,
        public ?string $telefono,
        public float $litrosPromedio,
    ) {
    }
}
