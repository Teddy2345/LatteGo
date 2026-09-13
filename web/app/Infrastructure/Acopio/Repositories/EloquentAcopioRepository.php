<?php

declare(strict_types=1);

namespace App\Infrastructure\Acopio\Repositories;

use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\CantidadLitros;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;
use App\Domain\Acopio\ValueObjects\UbicacionCaptura;
use App\Infrastructure\Acopio\Models\AcopioModel;
use DateTimeImmutable;

final class EloquentAcopioRepository implements AcopioRepository
{
    public function guardar(Acopio $acopio): Acopio
    {
        $modelo = $acopio->id === null
            ? new AcopioModel()
            : AcopioModel::query()->findOrFail($acopio->id);

        $modelo->fill([
            'proveedor_id' => $acopio->proveedorId,
            'acopiador_id' => $acopio->acopiadorId,
            'ruta_id' => $acopio->rutaId,
            'fecha' => $acopio->fecha->format('Y-m-d'),
            'cantidad_litros' => $acopio->cantidadLitros->valor,
            'estado' => $acopio->estado->value,
            'perdida_litros' => $acopio->perdidaLitros,
            'motivo_perdida' => $acopio->motivoPerdida,
            'observaciones' => $acopio->observaciones,
            'latitud' => $acopio->ubicacion?->latitud,
            'longitud' => $acopio->ubicacion?->longitud,
            'precision_m' => $acopio->ubicacion?->precisionMetros,
            'capturado_en' => $acopio->ubicacion?->capturadoEn->format('Y-m-d H:i:s'),
            'request_id' => $acopio->requestId,
            'movilidad_id' => $acopio->movilidadId,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Acopio
    {
        $modelo = AcopioModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function buscarPorRequestId(string $requestId): ?Acopio
    {
        $modelo = AcopioModel::query()->where('request_id', $requestId)->first();

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarTodos(): array
    {
        return AcopioModel::query()
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return AcopioModel::query()
            ->where('proveedor_id', $proveedorId)
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorAcopiador(int $acopiadorId): array
    {
        return AcopioModel::query()
            ->where('acopiador_id', $acopiadorId)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorFecha(DateTimeImmutable $fecha): array
    {
        return AcopioModel::query()
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->orderByDesc('id')
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorMovilidadYFecha(int $movilidadId, DateTimeImmutable $fecha): array
    {
        return AcopioModel::query()
            ->where('movilidad_id', $movilidadId)
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPendientesSincronizacion(): array
    {
        return AcopioModel::query()
            ->where('estado', EstadoAcopio::PendienteSincronizar->value)
            ->orderBy('fecha')
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorSemana(DateTimeImmutable $inicio, DateTimeImmutable $fin): array
    {
        return AcopioModel::query()
            ->whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->orderBy('fecha')
            ->get()
            ->map(fn (AcopioModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    private function aDominio(AcopioModel $modelo): Acopio
    {
        return Acopio::reconstituir(
            id: $modelo->id,
            proveedorId: $modelo->proveedor_id,
            acopiadorId: $modelo->acopiador_id,
            rutaId: $modelo->ruta_id,
            fecha: new DateTimeImmutable($modelo->fecha->format('Y-m-d')),
            cantidadLitros: new CantidadLitros((float) $modelo->cantidad_litros),
            estado: EstadoAcopio::from($modelo->estado),
            observaciones: $modelo->observaciones,
            perdidaLitros: $modelo->perdida_litros === null ? null : (float) $modelo->perdida_litros,
            motivoPerdida: $modelo->motivo_perdida,
            ubicacion: $this->aUbicacion($modelo),
            requestId: $modelo->request_id,
            movilidadId: $modelo->movilidad_id,
        );
    }

    private function aUbicacion(AcopioModel $modelo): ?UbicacionCaptura
    {
        if ($modelo->latitud === null || $modelo->longitud === null || $modelo->precision_m === null) {
            return null;
        }

        return new UbicacionCaptura(
            latitud: (float) $modelo->latitud,
            longitud: (float) $modelo->longitud,
            precisionMetros: (float) $modelo->precision_m,
            capturadoEn: new DateTimeImmutable(
                ($modelo->capturado_en ?? $modelo->created_at)->format('Y-m-d H:i:s')
            ),
        );
    }
}
