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
            usuarioId: $notificacion->usuarioId,
        );

        $this->notificaciones[$guardada->id] = $guardada;

        return $guardada;
    }

    public function listarRecientes(int $limite, ?int $usuarioId = null, bool $soloPropias = false): array
    {
        $visibles = array_values(array_filter(
            $this->notificaciones,
            static fn (Notificacion $n): bool => $usuarioId === null
                || ($soloPropias ? $n->usuarioId === $usuarioId : $n->usuarioId === null || $n->usuarioId === $usuarioId),
        ));

        return array_slice(array_reverse($visibles), 0, $limite);
    }

    public function idsLeidasPor(int $userId, array $notificacionIds): array
    {
        return [];
    }

    public function marcarLeida(int $notificacionId, int $userId): void
    {
    }

    public function contarNoLeidasPara(int $userId, bool $soloPropias = false): int
    {
        return count($this->listarRecientes(PHP_INT_MAX, $userId, $soloPropias));
    }
}
