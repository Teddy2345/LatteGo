<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

final readonly class MovilidadDetalleData
{
    /**
     * @param  ProveedorRecorridoData[]  $proveedores
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipo,
        public ?string $rutaNombre,
        public float $litrosRecolectadosHoy,
        public int $progresoPorcentaje,
        public array $proveedores,
    ) {
    }
}
