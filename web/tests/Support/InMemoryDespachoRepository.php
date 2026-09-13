<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Produccion\Entities\Despacho;
use App\Domain\Produccion\Repositories\DespachoRepository;

final class InMemoryDespachoRepository implements DespachoRepository
{
    /** @var array<int, Despacho> */
    private array $despachos = [];

    private int $siguienteId = 1;

    public function guardar(Despacho $despacho): Despacho
    {
        $guardado = $despacho->id === null
            ? Despacho::reconstituir(
                id: $this->siguienteId++,
                produccionId: $despacho->produccionId,
                despachadorId: $despacho->despachadorId,
                quesosRecibidos: $despacho->quesosRecibidos,
                quesosDespachados: $despacho->quesosDespachados,
                merma: $despacho->merma,
                observaciones: $despacho->observaciones,
            )
            : $despacho;

        $this->despachos[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Despacho
    {
        return $this->despachos[$id] ?? null;
    }

    public function listarPorProduccion(int $produccionId): array
    {
        return array_values(array_filter(
            $this->despachos,
            static fn (Despacho $d): bool => $d->produccionId === $produccionId,
        ));
    }

    public function listarTodos(): array
    {
        return array_values($this->despachos);
    }
}
