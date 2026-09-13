<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Produccion\Entities\Produccion;
use App\Domain\Produccion\Repositories\ProduccionRepository;

final class InMemoryProduccionRepository implements ProduccionRepository
{
    /** @var array<int, Produccion> */
    private array $producciones = [];

    private int $siguienteId = 1;

    public function guardar(Produccion $produccion): Produccion
    {
        $guardado = $produccion->id === null
            ? Produccion::reconstituir(
                id: $this->siguienteId++,
                fecha: $produccion->fecha,
                litrosProcesados: $produccion->litrosProcesados,
                quesosProducidos: $produccion->quesosProducidos,
                rendimiento: $produccion->rendimiento,
                jefaProduccionId: $produccion->jefaProduccionId,
                observaciones: $produccion->observaciones,
                productoId: $produccion->productoId,
            )
            : $produccion;

        $this->producciones[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Produccion
    {
        return $this->producciones[$id] ?? null;
    }

    public function listarTodos(): array
    {
        return array_values($this->producciones);
    }
}
