<?php

declare(strict_types=1);

namespace App\Application\Notificacion\DTOs;

use App\Domain\Notificacion\Entities\Notificacion;

final readonly class NotificacionData
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function __construct(
        public int $id,
        public string $tipo,
        public string $titulo,
        public string $mensaje,
        public string $nivel,
        public array $datos,
        public ?string $creadaEn,
        public bool $leida,
    ) {
    }

    public static function desdeEntidad(Notificacion $notificacion, bool $leida): self
    {
        return new self(
            id: (int) $notificacion->id,
            tipo: $notificacion->tipo,
            titulo: $notificacion->titulo,
            mensaje: $notificacion->mensaje,
            nivel: $notificacion->nivel->value,
            datos: $notificacion->datos,
            creadaEn: $notificacion->creadaEn?->format('Y-m-d H:i:s'),
            leida: $leida,
        );
    }
}
