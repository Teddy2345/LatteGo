<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditoria\Repositories;

use App\Domain\Auditoria\Entities\RegistroActividad;
use App\Domain\Auditoria\Repositories\RegistroActividadRepository;
use DateTimeImmutable;
use Spatie\Activitylog\Models\Activity;

/**
 * Lee el historial que spatie/laravel-activitylog escribe automaticamente
 * desde los modelos Eloquent de cada modulo.
 */
final class EloquentRegistroActividadRepository implements RegistroActividadRepository
{
    public function listarRecientes(int $limite): array
    {
        return Activity::query()
            ->with('causer')
            ->latest('id')
            ->limit($limite)
            ->get()
            ->map(fn (Activity $actividad): RegistroActividad => new RegistroActividad(
                id: $actividad->id,
                modulo: $actividad->log_name ?? 'sistema',
                descripcion: $actividad->description,
                evento: $actividad->event,
                usuario: $actividad->causer?->name,
                ocurridoEn: new DateTimeImmutable($actividad->created_at->format('Y-m-d H:i:s')),
            ))
            ->all();
    }
}
