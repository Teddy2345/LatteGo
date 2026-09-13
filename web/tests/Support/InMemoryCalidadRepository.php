<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Domain\Calidad\ValueObjects\MotivoRechazo;

final class InMemoryCalidadRepository implements CalidadRepository
{
    /** @var array<int, Calidad> */
    private array $analisis = [];

    private int $siguienteId = 1;

    public function guardar(Calidad $calidad): Calidad
    {
        $guardado = $calidad->id === null
            ? Calidad::reconstituir(
                id: $this->siguienteId++,
                proveedorId: $calidad->proveedorId,
                acopioId: $calidad->acopioId,
                fecha: $calidad->fecha,
                temperatura: $calidad->temperatura,
                grasa: $calidad->grasa,
                solidosNoGrasos: $calidad->solidosNoGrasos,
                densidad: $calidad->densidad,
                proteina: $calidad->proteina,
                lactosa: $calidad->lactosa,
                sales: $calidad->sales,
                aguaAgregada: $calidad->aguaAgregada,
                ph: $calidad->ph,
                pruebaAlcohol: $calidad->pruebaAlcohol,
                resultado: $calidad->resultado,
                motivoRechazo: $calidad->motivoRechazo,
                sancionAplicada: $calidad->sancionAplicada,
                fotoPath: $calidad->fotoPath,
            )
            : $calidad;

        $this->analisis[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Calidad
    {
        return $this->analisis[$id] ?? null;
    }

    public function listarTodos(): array
    {
        return array_values($this->analisis);
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return array_values(array_filter(
            $this->analisis,
            static fn (Calidad $c): bool => $c->proveedorId === $proveedorId,
        ));
    }

    public function contarAdulteracionesPrevias(int $proveedorId): int
    {
        return count(array_filter(
            $this->analisis,
            static fn (Calidad $c): bool => $c->proveedorId === $proveedorId && $c->motivoRechazo === MotivoRechazo::Adulteracion,
        ));
    }
}
