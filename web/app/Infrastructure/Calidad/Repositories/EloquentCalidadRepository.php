<?php

declare(strict_types=1);

namespace App\Infrastructure\Calidad\Repositories;

use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Domain\Calidad\ValueObjects\Densidad;
use App\Domain\Calidad\ValueObjects\MotivoRechazo;
use App\Domain\Calidad\ValueObjects\Ph;
use App\Domain\Calidad\ValueObjects\PorcentajeAguaAgregada;
use App\Domain\Calidad\ValueObjects\PruebaAlcohol;
use App\Domain\Calidad\ValueObjects\ResultadoCalidad;
use App\Domain\Calidad\ValueObjects\SancionAplicada;
use App\Infrastructure\Calidad\Models\CalidadModel;

final class EloquentCalidadRepository implements CalidadRepository
{
    public function guardar(Calidad $calidad): Calidad
    {
        $modelo = $calidad->id === null
            ? new CalidadModel()
            : CalidadModel::query()->findOrFail($calidad->id);

        $modelo->fill([
            'proveedor_id' => $calidad->proveedorId,
            'acopio_id' => $calidad->acopioId,
            'fecha' => $calidad->fecha->format('Y-m-d'),
            'temperatura' => $calidad->temperatura,
            'grasa' => $calidad->grasa,
            'solidos_no_grasos' => $calidad->solidosNoGrasos,
            'densidad' => $calidad->densidad->valor,
            'proteina' => $calidad->proteina,
            'lactosa' => $calidad->lactosa,
            'sales' => $calidad->sales,
            'agua_agregada' => $calidad->aguaAgregada->valor,
            'ph' => $calidad->ph->valor,
            'prueba_alcohol' => $calidad->pruebaAlcohol->value,
            'resultado' => $calidad->resultado->value,
            'motivo_rechazo' => $calidad->motivoRechazo?->value,
            'sancion_aplicada' => $calidad->sancionAplicada->value,
            'foto_path' => $calidad->fotoPath,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Calidad
    {
        $modelo = CalidadModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarTodos(): array
    {
        return CalidadModel::query()
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (CalidadModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return CalidadModel::query()
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (CalidadModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function contarAdulteracionesPrevias(int $proveedorId): int
    {
        return CalidadModel::query()
            ->where('proveedor_id', $proveedorId)
            ->where('motivo_rechazo', MotivoRechazo::Adulteracion->value)
            ->count();
    }

    private function aDominio(CalidadModel $modelo): Calidad
    {
        return Calidad::reconstituir(
            id: $modelo->id,
            proveedorId: $modelo->proveedor_id,
            acopioId: $modelo->acopio_id,
            fecha: new \DateTimeImmutable($modelo->fecha->format('Y-m-d')),
            temperatura: (float) $modelo->temperatura,
            grasa: (float) $modelo->grasa,
            solidosNoGrasos: (float) $modelo->solidos_no_grasos,
            densidad: new Densidad((float) $modelo->densidad),
            proteina: (float) $modelo->proteina,
            lactosa: (float) $modelo->lactosa,
            sales: (float) $modelo->sales,
            aguaAgregada: new PorcentajeAguaAgregada((float) $modelo->agua_agregada),
            ph: new Ph((float) $modelo->ph),
            pruebaAlcohol: PruebaAlcohol::from($modelo->prueba_alcohol),
            resultado: ResultadoCalidad::from($modelo->resultado),
            motivoRechazo: $modelo->motivo_rechazo === null ? null : MotivoRechazo::from($modelo->motivo_rechazo),
            sancionAplicada: SancionAplicada::from($modelo->sancion_aplicada),
            fotoPath: $modelo->foto_path,
        );
    }
}
