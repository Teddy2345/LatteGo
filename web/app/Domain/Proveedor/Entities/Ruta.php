<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Entities;

final readonly class Ruta
{
    public function __construct(
        public int $id,
        public string $nombre,
    ) {
    }
}
