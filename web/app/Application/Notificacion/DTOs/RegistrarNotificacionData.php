<?php

declare(strict_types=1);

namespace App\Application\Notificacion\DTOs;

use App\Domain\Notificacion\ValueObjects\NivelNotificacion;

final readonly class RegistrarNotificacionData
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function __construct(
        public string $tipo,
        public string $titulo,
        public string $mensaje,
        public NivelNotificacion $nivel,
        public array $datos = [],
    ) {
    }
}
