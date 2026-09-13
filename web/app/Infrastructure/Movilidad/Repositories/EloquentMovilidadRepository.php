<?php

declare(strict_types=1);

namespace App\Infrastructure\Movilidad\Repositories;

use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Exceptions\MovilidadConHistorialException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Movilidad\ValueObjects\TipoMovilidad;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Movilidad\Models\MovilidadPersonaModel;
use Illuminate\Database\QueryException;

final class EloquentMovilidadRepository implements MovilidadRepository
{
    public function guardar(Movilidad $movilidad): Movilidad
    {
        $modelo = $movilidad->id === null
            ? new MovilidadModel()
            : MovilidadModel::query()->findOrFail($movilidad->id);

        $modelo->fill([
            'nombre' => $movilidad->nombre,
            'tipo' => $movilidad->tipo->value,
            'ruta_id' => $movilidad->rutaId,
            'activa' => $movilidad->activa,
            'usuario_id' => $movilidad->usuarioId,
        ]);

        $modelo->save();

        return $this->aDominio($modelo->fresh('personas'));
    }

    public function buscarPorId(int $id): ?Movilidad
    {
        $modelo = MovilidadModel::query()->with('personas')->find($id);

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function listarTodas(): array
    {
        return MovilidadModel::query()
            ->with('personas')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get()
            ->map(fn (MovilidadModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function listarPorRuta(int $rutaId): array
    {
        return MovilidadModel::query()
            ->with('personas')
            ->where('ruta_id', $rutaId)
            ->orderBy('nombre')
            ->get()
            ->map(fn (MovilidadModel $modelo) => $this->aDominio($modelo))
            ->all();
    }

    public function buscarPorUsuario(int $usuarioId): ?Movilidad
    {
        $modelo = MovilidadModel::query()->with('personas')->where('usuario_id', $usuarioId)->first();

        return $modelo === null ? null : $this->aDominio($modelo);
    }

    public function desactivarHermanas(int $rutaId, int $exceptoMovilidadId): void
    {
        MovilidadModel::query()
            ->where('ruta_id', $rutaId)
            ->whereKeyNot($exceptoMovilidadId)
            ->update(['activa' => false]);
    }

    public function agregarPersona(int $movilidadId, string $nombre): void
    {
        MovilidadPersonaModel::query()->create(['movilidad_id' => $movilidadId, 'nombre' => $nombre]);
    }

    public function quitarPersona(int $personaId): void
    {
        MovilidadPersonaModel::query()->whereKey($personaId)->delete();
    }

    public function eliminar(int $id): void
    {
        try {
            MovilidadModel::query()->whereKey($id)->firstOrFail()->delete();
        } catch (QueryException $e) {
            throw new MovilidadConHistorialException(
                'No se puede quitar este camion: ya tiene acopios registrados.',
                previous: $e,
            );
        }
    }

    private function aDominio(MovilidadModel $modelo): Movilidad
    {
        return Movilidad::reconstituir(
            id: $modelo->id,
            nombre: $modelo->nombre,
            tipo: TipoMovilidad::from($modelo->tipo),
            rutaId: $modelo->ruta_id,
            activa: (bool) $modelo->activa,
            personas: $modelo->personas->map(fn (MovilidadPersonaModel $p): array => ['id' => $p->id, 'nombre' => $p->nombre])->all(),
            usuarioId: $modelo->usuario_id,
        );
    }
}
