<?php

declare(strict_types=1);

namespace App\Infrastructure\Movilidad\Repositories;

use App\Domain\Movilidad\Entities\IncidenciaRecorrido;
use App\Domain\Movilidad\Repositories\IncidenciaRecorridoRepository;
use App\Infrastructure\Movilidad\Models\IncidenciaRecorridoModel;
use DateTimeImmutable;

final class EloquentIncidenciaRecorridoRepository implements IncidenciaRecorridoRepository
{
    public function guardar(IncidenciaRecorrido $incidencia): IncidenciaRecorrido
    {
        $modelo = IncidenciaRecorridoModel::query()->create([
            'proveedor_id' => $incidencia->proveedorId,
            'movilidad_id' => $incidencia->movilidadId,
            'fecha' => $incidencia->fecha->format('Y-m-d'),
            'tipo' => 'no_entrego',
            'motivo' => $incidencia->motivo,
            'registrado_por' => $incidencia->registradoPor,
        ]);

        return $this->aDominio($modelo);
    }

    public function listarPorMovilidadYFecha(int $movilidadId, DateTimeImmutable $fecha): array
    {
        $indexadas = [];

        foreach (
            IncidenciaRecorridoModel::query()
                ->where('movilidad_id', $movilidadId)
                ->whereDate('fecha', $fecha->format('Y-m-d'))
                ->get() as $modelo
        ) {
            $indexadas[$modelo->proveedor_id] = $this->aDominio($modelo);
        }

        return $indexadas;
    }

    private function aDominio(IncidenciaRecorridoModel $modelo): IncidenciaRecorrido
    {
        return new IncidenciaRecorrido(
            id: $modelo->id,
            proveedorId: $modelo->proveedor_id,
            movilidadId: $modelo->movilidad_id,
            fecha: new DateTimeImmutable($modelo->fecha->format('Y-m-d')),
            motivo: $modelo->motivo,
            registradoPor: $modelo->registrado_por,
        );
    }
}
