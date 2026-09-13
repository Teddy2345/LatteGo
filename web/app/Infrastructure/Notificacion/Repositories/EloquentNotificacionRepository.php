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
        ]);

        return $this->aDominio($modelo);
    }

    public function listarRecientes(int $limite): array
    {
        return NotificacionModel::query()
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

    public function contarNoLeidasPara(int $userId): int
    {
        return NotificacionModel::query()
            ->whereDoesntHave('lecturas', fn ($q) => $q->where('user_id', $userId))
            ->count();
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
        );
    }
}
