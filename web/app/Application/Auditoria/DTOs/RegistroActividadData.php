<?php

declare(strict_types=1);

namespace App\Application\Auditoria\DTOs;

use App\Domain\Auditoria\Entities\RegistroActividad;

final readonly class RegistroActividadData
{
    public function __construct(
        public int $id,
        public string $modulo,
        public string $descripcion,
        public ?string $evento,
        public ?string $usuario,
        public string $ocurridoEn,
    ) {
    }

    public static function desdeEntidad(RegistroActividad $registro): self
    {
        return new self(
            id: $registro->id,
            modulo: $registro->modulo,
            descripcion: $registro->descripcion,
            evento: $registro->evento,
            usuario: $registro->usuario,
            ocurridoEn: $registro->ocurridoEn->format('Y-m-d H:i:s'),
        );
    }
}
