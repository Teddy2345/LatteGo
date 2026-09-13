<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Domain\Acopio\ValueObjects\EstadoAcopio;

final class InMemoryAcopioRepository implements AcopioRepository
{
    /** @var array<int, Acopio> */
    private array $acopios = [];

    private int $siguienteId = 1;

    public function guardar(Acopio $acopio): Acopio
    {
        $guardado = $acopio->id === null
            ? Acopio::reconstituir(
                id: $this->siguienteId++,
                proveedorId: $acopio->proveedorId,
                acopiadorId: $acopio->acopiadorId,
                rutaId: $acopio->rutaId,
                fecha: $acopio->fecha,
                cantidadLitros: $acopio->cantidadLitros,
                estado: $acopio->estado,
                observaciones: $acopio->observaciones,
                perdidaLitros: $acopio->perdidaLitros,
                motivoPerdida: $acopio->motivoPerdida,
                ubicacion: $acopio->ubicacion,
                requestId: $acopio->requestId,
                movilidadId: $acopio->movilidadId,
            )
            : $acopio;

        $this->acopios[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Acopio
    {
        return $this->acopios[$id] ?? null;
    }

    public function buscarPorRequestId(string $requestId): ?Acopio
    {
        foreach ($this->acopios as $acopio) {
            if ($acopio->requestId === $requestId) {
                return $acopio;
            }
        }

        return null;
    }

    public function listarTodos(): array
    {
        return array_values($this->acopios);
    }

    public function listarPorAcopiador(int $acopiadorId): array
    {
        return array_values(array_filter(
            $this->acopios,
            static fn (Acopio $acopio): bool => $acopio->acopiadorId === $acopiadorId,
        ));
    }

    public function listarPorFecha(\DateTimeImmutable $fecha): array
    {
        return array_values(array_filter(
            $this->acopios,
            static fn (Acopio $acopio): bool => $acopio->fecha->format('Y-m-d') === $fecha->format('Y-m-d'),
        ));
    }

    public function listarPorProveedor(int $proveedorId): array
    {
        return array_values(array_filter(
            $this->acopios,
            static fn (Acopio $acopio): bool => $acopio->proveedorId === $proveedorId,
        ));
    }

    public function listarPorMovilidadYFecha(int $movilidadId, \DateTimeImmutable $fecha): array
    {
        return array_values(array_filter(
            $this->acopios,
            static fn (Acopio $acopio): bool => $acopio->movilidadId === $movilidadId
                && $acopio->fecha->format('Y-m-d') === $fecha->format('Y-m-d'),
        ));
    }

    public function listarPendientesSincronizacion(): array
    {
        return array_values(array_filter(
            $this->acopios,
            static fn (Acopio $acopio): bool => $acopio->estado === EstadoAcopio::PendienteSincronizar,
        ));
    }

    public function listarPorSemana(\DateTimeImmutable $inicio, \DateTimeImmutable $fin): array
    {
        return array_values(array_filter(
            $this->acopios,
            static fn (Acopio $acopio): bool => $acopio->fecha >= $inicio && $acopio->fecha <= $fin,
        ));
    }
}
