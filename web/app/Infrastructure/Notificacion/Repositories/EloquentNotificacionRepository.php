<?php

declare(strict_types=1);

namespace App\Infrastructure\Notificacion\Repositories;

use App\Domain\Notificacion\Entities\Notificacion;
use App\Domain\Notificacion\Repositories\NotificacionRepository;
use App\Domain\Notificacion\ValueObjects\NivelNotificacion;
use App\Infrastructure\Notificacion\Models\NotificacionLecturaModel;
use App\Infrastructure\Notificacion\Models\NotificacionModel;
use DateTimeImmutable;

final class EloquentNotificacionRepository implements NotificacionRepository
{
    public function guardar(Notificacion $notificacion): Notificacion
    {
        $modelo = NotificacionModel::query()->create([
            'tipo' => $notificacion->tipo,
            'titulo' => $notificacion->titulo,
            'mensaje' => $notificacion->mensaje,
            'nivel' => $notificacion->nivel->value,
            'datos' => $notificacion->datos,
            'user_id' => $notificacion->usuarioId,
        ]);

        return $this->aDominio($modelo);
    }

    public function listarRecientes(int $limite, ?int $usuarioId = null, bool $soloPropias = false): array
    {
        return NotificacionModel::query()
            ->tap(fn ($q) => $this->acotarA($q, $usuarioId, $soloPropias))
            ->latest('id')
            ->limit($limite)
            ->get()
            ->map(fn (NotificacionModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function idsLeidasPor(int $userId, array $notificacionIds): array
    {
        if ($notificacionIds === []) {
            return [];
        }

        return NotificacionLecturaModel::query()
            ->where('user_id', $userId)
            ->whereIn('notificacion_id', $notificacionIds)
            ->pluck('notificacion_id')
            ->all();
    }

    public function marcarLeida(int $notificacionId, int $userId): void
    {
        NotificacionLecturaModel::query()->firstOrCreate(
            ['notificacion_id' => $notificacionId, 'user_id' => $userId],
            ['leida_en' => now()],
        );
    }

    public function contarNoLeidasPara(int $userId, bool $soloPropias = false): int
    {
        return NotificacionModel::query()
            ->tap(fn ($q) => $this->acotarA($q, $userId, $soloPropias))
            ->whereDoesntHave('lecturas', fn ($q) => $q->where('user_id', $userId))
            ->count();
    }

    /**
     * El personal ve las alertas de la planta y las suyas; el proveedor,
     * solo las suyas: las de la planta no le incumben.
     */
    private function acotarA($query, ?int $usuarioId, bool $soloPropias): void
    {
        if ($usuarioId === null) {
            return;
        }

        $soloPropias
            ? $query->where('user_id', $usuarioId)
            : $query->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $usuarioId));
    }

    private function aDominio(NotificacionModel $modelo): Notificacion
    {
        return new Notificacion(
            id: $modelo->id,
            tipo: $modelo->tipo,
            titulo: $modelo->titulo,
            mensaje: $modelo->mensaje,
            nivel: NivelNotificacion::from($modelo->nivel),
            datos: $modelo->datos ?? [],
            creadaEn: $modelo->created_at === null ? null : new DateTimeImmutable($modelo->created_at->format('Y-m-d H:i:s')),
            usuarioId: $modelo->user_id,
        );
    }
}
