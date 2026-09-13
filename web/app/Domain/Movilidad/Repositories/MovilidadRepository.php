<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Repositories;

use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Exceptions\MovilidadConHistorialException;

interface MovilidadRepository
{
    public function guardar(Movilidad $movilidad): Movilidad;

    public function buscarPorId(int $id): ?Movilidad;

    /**
     * @return Movilidad[]
     */
    public function listarTodas(): array;

    /**
     * @return Movilidad[]
     */
    public function listarPorRuta(int $rutaId): array;

    /** La movilidad de la que ese usuario es el acopiador titular, si tiene una asignada. */
    public function buscarPorUsuario(int $usuarioId): ?Movilidad;

    /**
     * Desactiva todas las demas movilidades de esa ruta, para que quede una
     * sola activa a la vez.
     */
    public function desactivarHermanas(int $rutaId, int $exceptoMovilidadId): void;

    public function agregarPersona(int $movilidadId, string $nombre): void;

    public function quitarPersona(int $personaId): void;

    /**
     * @throws MovilidadConHistorialException si el camion ya tiene acopios registrados.
     */
    public function eliminar(int $id): void;
}
