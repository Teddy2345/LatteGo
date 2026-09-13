<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Repositories;

use App\Domain\Proveedor\Entities\SolicitudCambioZona;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Domain\Proveedor\ValueObjects\EstadoSolicitudCambioZona;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use DateTimeImmutable;

final class EloquentSolicitudCambioZonaRepository implements SolicitudCambioZonaRepository
{
    public function guardar(SolicitudCambioZona $solicitud): SolicitudCambioZona
    {
        $modelo = $solicitud->id === null
            ? new SolicitudCambioZonaModel()
            : SolicitudCambioZonaModel::query()->findOrFail($solicitud->id);

        $modelo->fill([
            'proveedor_id' => $solicitud->proveedorId,
            'ruta_actual_id' => $solicitud->rutaActualId,
            'ruta_solicitada_id' => $solicitud->rutaSolicitadaId,
            'fecha_cambio' => $solicitud->fechaCambio->format('Y-m-d'),
            'motivo' => $solicitud->motivo,
            'estado' => $solicitud->estado->value,
            'solicitado_por' => $solicitud->solicitadoPor,
            'revisado_por' => $solicitud->revisadoPor,
            'fecha_revision' => $solicitud->fechaRevision?->format('Y-m-d H:i:s'),
            'observacion_revision' => $solicitud->observacionRevision,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?SolicitudCambioZona
    {
        $modelo = SolicitudCambioZonaModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function existePendientePara(int $proveedorId): bool
    {
        return SolicitudCambioZonaModel::query()
            ->where('proveedor_id', $proveedorId)
            ->where('estado', EstadoSolicitudCambioZona::Pendiente->value)
            ->exists();
    }

    public function listarPendientes(): array
    {
        return SolicitudCambioZonaModel::query()
            ->where('estado', EstadoSolicitudCambioZona::Pendiente->value)
            ->orderBy('created_at')
            ->get()
            ->map(fn (SolicitudCambioZonaModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return SolicitudCambioZonaModel::query()
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SolicitudCambioZonaModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPendientesIndexadasPorProveedor(): array
    {
        $indexadas = [];

        foreach (
            SolicitudCambioZonaModel::query()
                ->where('estado', EstadoSolicitudCambioZona::Pendiente->value)
                ->get() as $modelo
        ) {
            $indexadas[$modelo->proveedor_id] = $this->aDominio($modelo);
        }

        return $indexadas;
    }

    private function aDominio(SolicitudCambioZonaModel $modelo): SolicitudCambioZona
    {
        return SolicitudCambioZona::reconstituir(
            id: $modelo->id,
            proveedorId: $modelo->proveedor_id,
            rutaActualId: $modelo->ruta_actual_id,
            rutaSolicitadaId: $modelo->ruta_solicitada_id,
            fechaCambio: new DateTimeImmutable($modelo->fecha_cambio->format('Y-m-d')),
            motivo: $modelo->motivo,
            estado: EstadoSolicitudCambioZona::from($modelo->estado),
            solicitadoPor: $modelo->solicitado_por,
            revisadoPor: $modelo->revisado_por,
            fechaRevision: $modelo->fecha_revision === null ? null : new DateTimeImmutable($modelo->fecha_revision->format('Y-m-d H:i:s')),
            observacionRevision: $modelo->observacion_revision,
        );
    }
}
