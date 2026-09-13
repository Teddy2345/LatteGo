<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Notificacion\Entities\Notificacion;
use App\Domain\Notificacion\Repositories\NotificacionRepository;

final class InMemoryNotificacionRepository implements NotificacionRepository
{
    /** @var array<int, Notificacion> */
    private array $notificaciones = [];

    private int $siguienteId = 1;

    public function guardar(Notificacion $notificacion): Notificacion
    {
        $guardada = new Notificacion(
            id: $this->siguienteId++,
            tipo: $notificacion->tipo,
            titulo: $notificacion->titulo,
            mensaje: $notificacion->mensaje,
            nivel: $notificacion->nivel,
            datos: $notificacion->datos,
            creadaEn: new \DateTimeImmutable(),
        );

        $this->notificaciones[$guardada->id] = $guardada;

        return $guardada;
    }

    public function listarRecientes(int $limite): array
    {
        return array_slice(array_reverse(array_values($this->notificaciones)), 0, $limite);
    }

    public function idsLeidasPor(int $userId, array $notificacionIds): array
    {
        return [];
    }

    public function marcarLeida(int $notificacionId, int $userId): void
    {
    }

    public function contarNoLeidasPara(int $userId): int
    {
        return count($this->notificaciones);
    }
}
