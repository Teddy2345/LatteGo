<?php

declare(strict_types=1);

namespace App\Application\Proveedor\DTOs;

final readonly class RutaData
{
    public function __construct(
        public int $id,
        public string $nombre,
    ) {
    }
}
