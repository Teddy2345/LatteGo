<?php

declare(strict_types=1);

namespace App\Domain\Notificacion\Repositories;

use App\Domain\Notificacion\Entities\Notificacion;

interface NotificacionRepository
{
    public function guardar(Notificacion $notificacion): Notificacion;

    /**
     * @return Notificacion[]
     */
    public function listarRecientes(int $limite): array;

    /**
     * Ids de notificaciones (de las mas recientes) ya leidas por ese usuario.
     *
     * @param  int[]  $notificacionIds
     * @return int[]
     */
    public function idsLeidasPor(int $userId, array $notificacionIds): array;

    public function marcarLeida(int $notificacionId, int $userId): void;

    public function contarNoLeidasPara(int $userId): int;
}
