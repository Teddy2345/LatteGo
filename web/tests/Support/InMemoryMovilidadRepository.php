<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Exceptions\MovilidadConHistorialException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;

final class InMemoryMovilidadRepository implements MovilidadRepository
{
    /** @var array<int, Movilidad> */
    private array $movilidades = [];

    private int $siguienteId = 1;

    public function guardar(Movilidad $movilidad): Movilidad
    {
        $guardado = $movilidad->id === null
            ? Movilidad::reconstituir(
                id: $this->siguienteId++,
                nombre: $movilidad->nombre,
                tipo: $movilidad->tipo,
                rutaId: $movilidad->rutaId,
                activa: $movilidad->activa,
                personas: $movilidad->personas,
                usuarioId: $movilidad->usuarioId,
            )
            : $movilidad;

        $this->movilidades[$guardado->id] = $guardado;

        return $guardado;
    }

    public function buscarPorId(int $id): ?Movilidad
    {
        return $this->movilidades[$id] ?? null;
    }

    public function listarTodas(): array
    {
        return array_values($this->movilidades);
    }

    public function listarPorRuta(int $rutaId): array
    {
        return array_values(array_filter(
            $this->movilidades,
            static fn (Movilidad $m): bool => $m->rutaId === $rutaId,
        ));
    }

    public function buscarPorUsuario(int $usuarioId): ?Movilidad
    {
        foreach ($this->movilidades as $movilidad) {
            if ($movilidad->usuarioId === $usuarioId) {
                return $movilidad;
            }
        }

        return null;
    }

    public function desactivarHermanas(int $rutaId, int $exceptoMovilidadId): void
    {
        foreach ($this->movilidades as $id => $movilidad) {
            if ($movilidad->rutaId === $rutaId && $id !== $exceptoMovilidadId) {
                $this->movilidades[$id] = $movilidad->desactivar();
            }
        }
    }

    public function agregarPersona(int $movilidadId, string $nombre): void
    {
        $movilidad = $this->movilidades[$movilidadId];
        $personas = $movilidad->personas;
        $personas[] = ['id' => count($personas) + 1, 'nombre' => $nombre];

        $this->movilidades[$movilidadId] = Movilidad::reconstituir(
            id: $movilidad->id,
            nombre: $movilidad->nombre,
            tipo: $movilidad->tipo,
            rutaId: $movilidad->rutaId,
            activa: $movilidad->activa,
            personas: $personas,
            usuarioId: $movilidad->usuarioId,
        );
    }

    public function quitarPersona(int $personaId): void
    {
        foreach ($this->movilidades as $id => $movilidad) {
            $personas = array_values(array_filter(
                $movilidad->personas,
                static fn (array $p): bool => $p['id'] !== $personaId,
            ));

            $this->movilidades[$id] = Movilidad::reconstituir(
                id: $movilidad->id,
                nombre: $movilidad->nombre,
                tipo: $movilidad->tipo,
                rutaId: $movilidad->rutaId,
                activa: $movilidad->activa,
                personas: $personas,
                usuarioId: $movilidad->usuarioId,
            );
        }
    }

    public function eliminar(int $id): void
    {
        if (! isset($this->movilidades[$id])) {
            return;
        }

        if ($this->movilidades[$id]->personas !== []) {
            throw new MovilidadConHistorialException('No se puede eliminar una movilidad con historial.');
        }

        unset($this->movilidades[$id]);
    }
}
