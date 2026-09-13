<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadData;
use App\Domain\Movilidad\Exceptions\MovilidadNoEncontradaException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;

/**
 * Fija (o quita, con usuarioId null) al acopiador titular de una movilidad:
 * la persona real a quien pertenecen esa ruta y sus proveedores. Un usuario
 * solo puede ser titular de una movilidad a la vez; si ya tenia otra
 * asignada, se la quita antes de darle esta.
 */
final class AsignarUsuarioAMovilidadUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    public function ejecutar(int $movilidadId, ?int $usuarioId): MovilidadData
    {
        $movilidad = $this->movilidades->buscarPorId($movilidadId);

        if ($movilidad === null) {
            throw new MovilidadNoEncontradaException("No existe una movilidad con id {$movilidadId}.");
        }

        if ($usuarioId !== null) {
            $movilidadPrevia = $this->movilidades->buscarPorUsuario($usuarioId);

            if ($movilidadPrevia !== null && $movilidadPrevia->id !== $movilidadId) {
                $this->movilidades->guardar($movilidadPrevia->asignarUsuario(null));
            }
        }

        return MovilidadData::desdeEntidad($this->movilidades->guardar($movilidad->asignarUsuario($usuarioId)));
    }
}
