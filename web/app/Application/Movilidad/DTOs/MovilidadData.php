<?php

declare(strict_types=1);

namespace App\Application\Movilidad\DTOs;

use App\Domain\Movilidad\Entities\Movilidad;

final readonly class MovilidadData
{
    /**
     * @param  array<int, array{id: int, nombre: string}>  $personas
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public string $tipo,
        public string $tipoEtiqueta,
        public ?int $rutaId,
        public bool $activa,
        public array $personas,
        public ?int $usuarioId = null,
    ) {
    }

    public static function desdeEntidad(Movilidad $movilidad): self
    {
        return new self(
            id: (int) $movilidad->id,
            nombre: $movilidad->nombre,
            tipo: $movilidad->tipo->value,
            tipoEtiqueta: $movilidad->tipo->etiqueta(),
            rutaId: $movilidad->rutaId,
            activa: $movilidad->activa,
            personas: $movilidad->personas,
            usuarioId: $movilidad->usuarioId,
        );
    }
}
