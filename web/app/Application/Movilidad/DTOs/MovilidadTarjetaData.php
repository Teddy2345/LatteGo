<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

final readonly class MovilidadTarjetaData
{
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipo,
        public ?string $rutaNombre,
        public int $totalProveedores,
        public int $proveedoresAtendidos,
        public float $litrosRecolectadosHoy,
        public int $progresoPorcentaje,
    ) {
    }
}
