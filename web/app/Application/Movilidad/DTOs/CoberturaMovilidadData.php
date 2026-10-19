<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

final readonly class CoberturaMovilidadData
{
    /**
     * @param  string[]  $paradasReferencia
     * @param  ProveedorCoberturaData[]  $proveedores
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipo,
        public ?int $usuarioId,
        public ?string $rutaNombre,
        public array $paradasReferencia,
        public array $proveedores,
    ) {
    }
}
