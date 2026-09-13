<?php

declare(strict_types=1);

namespace App\Application\Notificacion\UseCases;

use App\Application\Notificacion\DTOs\NotificacionData;
use App\Domain\Notificacion\Entities\Notificacion;
use App\Domain\Notificacion\Repositories\NotificacionRepository;

final class ListarNotificacionesUseCase
{
    public const LIMITE_POR_DEFECTO = 50;

    public function __construct(
        private readonly NotificacionRepository $notificaciones,
    ) {
    }

    /**
     * @return NotificacionData[]
     */
    public function ejecutar(int $userId, int $limite = self::LIMITE_POR_DEFECTO): array
    {
        $recientes = $this->notificaciones->listarRecientes($limite);

        $idsLeidas = array_flip($this->notificaciones->idsLeidasPor(
            $userId,
            array_map(static fn (Notificacion $n): int => (int) $n->id, $recientes),
        ));

        return array_map(
            static fn (Notificacion $n): NotificacionData => NotificacionData::desdeEntidad($n, isset($idsLeidas[$n->id])),
            $recientes,
        );
    }
}
