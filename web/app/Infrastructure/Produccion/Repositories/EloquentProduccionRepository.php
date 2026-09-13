<?php

declare(strict_types=1);

namespace App\Infrastructure\Produccion\Repositories;

use App\Domain\Produccion\Entities\Produccion;
use App\Domain\Produccion\Repositories\ProduccionRepository;
use App\Domain\Produccion\ValueObjects\LitrosProcesados;
use App\Domain\Produccion\ValueObjects\QuesosProducidos;
use App\Domain\Produccion\ValueObjects\RendimientoPorcentaje;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use DateTimeImmutable;

final class EloquentProduccionRepository implements ProduccionRepository
{
    public function guardar(Produccion $produccion): Produccion
    {
        $modelo = $produccion->id === null
            ? new ProduccionModel()
            : ProduccionModel::query()->findOrFail($produccion->id);

        $modelo->fill([
            'fecha' => $produccion->fecha->format('Y-m-d'),
            'litros_procesados' => $produccion->litrosProcesados->valor,
            'quesos_producidos' => $produccion->quesosProducidos->valor,
            'producto_id' => $produccion->productoId,
            'rendimiento_porcentaje' => $produccion->rendimiento->valor,
            'jefa_produccion_id' => $produccion->jefaProduccionId,
            'observaciones' => $produccion->observaciones,
        ]);

        $modelo->save();

        return $this->aDominio($modelo);
    }

    public function buscarPorId(int $id): ?Produccion
    {
        $modelo = ProduccionModel::query()->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarTodos(): array
    {
        return ProduccionModel::query()
            ->orderByDesc('fecha')
            ->get()
            ->map(fn (ProduccionModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    private function aDominio(ProduccionModel $modelo): Produccion
    {
        $litros = new LitrosProcesados((float) $modelo->litros_procesados);
        $quesos = new QuesosProducidos($modelo->quesos_producidos);

        return Produccion::reconstituir(
            id: $modelo->id,
            fecha: new DateTimeImmutable($modelo->fecha->format('Y-m-d')),
            litrosProcesados: $litros,
            quesosProducidos: $quesos,
            rendimiento: RendimientoPorcentaje::calcular($litros, $quesos),
            jefaProduccionId: $modelo->jefa_produccion_id,
            observaciones: $modelo->observaciones,
            productoId: $modelo->producto_id,
        );
    }
}
