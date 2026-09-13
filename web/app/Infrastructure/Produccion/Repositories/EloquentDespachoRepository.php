<?php

declare(strict_types=1);

namespace App\Infrastructure\Produccion\Repositories;

use App\Domain\Produccion\Entities\Despacho;
use App\Domain\Produccion\Repositories\DespachoRepository;
use App\Infrastructure\Produccion\Models\DespachoModel;

final class EloquentDespachoRepository implements DespachoRepository
{
    public function guardar(Despacho $despacho): Despacho
    {
        $modelo = $despacho->id === null
            ? new DespachoModel()
            : DespachoModel::query()->findOrFail($despacho->id);

        $modelo->fill([
            'produccion_id' => $despacho->produccionId,
            'despachador_id' => $despacho->despachadorId,
            'quesos_recibidos' => $despacho->quesosRecibidos,
            'quesos_despachados' => $despacho->quesosDespachados,
            'merma' => $despacho->merma,
            'observaciones' => $despacho->observaciones,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Despacho
    {
        $modelo = DespachoModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarPorProduccion(int $produccionId): array
    {
        return DespachoModel::query()
            ->where('produccion_id', $produccionId)
            ->get()
            ->map(fn (DespachoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarTodos(): array
    {
        return DespachoModel::query()
            ->get()
            ->map(fn (DespachoModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    private function aDominio(DespachoModel $modelo): Despacho
    {
        return Despacho::reconstituir(
            id: $modelo->id,
            produccionId: $modelo->produccion_id,
            despachadorId: $modelo->despachador_id,
            quesosRecibidos: $modelo->quesos_recibidos,
            quesosDespachados: $modelo->quesos_despachados,
            merma: $modelo->merma,
            observaciones: $modelo->observaciones,
        );
    }
}
