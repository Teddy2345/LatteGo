<?php

declare(strict_types=1);

namespace App\Application\Notificacion\UseCases;

use App\Domain\Notificacion\Repositories\NotificacionRepository;

final class ContarNotificacionesNoLeidasUseCase
{
    public function __construct(
        private readonly NotificacionRepository $notificaciones,
    ) {
    }

    public function ejecutar(int $userId): int
    {
        return $this->notificaciones->contarNoLeidasPara($userId);
    }
}
