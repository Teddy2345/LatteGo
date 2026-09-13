<?php

declare(strict_types=1);

namespace App\Application\Notificacion\UseCases;

use App\Domain\Notificacion\Repositories\NotificacionRepository;

final class MarcarNotificacionLeidaUseCase
{
    public function __construct(
        private readonly NotificacionRepository $notificaciones,
    ) {
    }

    public function ejecutar(int $notificacionId, int $userId): void
    {
        $this->notificaciones->marcarLeida($notificacionId, $userId);
    }
}
