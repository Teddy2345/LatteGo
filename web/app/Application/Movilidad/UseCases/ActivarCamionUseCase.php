<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadData;
use App\Domain\Movilidad\Exceptions\MovilidadNoEncontradaException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;

/**
 * Marca este camion como el que hoy ejecuta el recorrido de su ruta y
 * desactiva a los demas camiones de esa misma ruta, para que quede uno solo
 * activo a la vez.
 */
final class ActivarCamionUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    public function ejecutar(int $movilidadId): MovilidadData
    {
        $movilidad = $this->movilidades->buscarPorId($movilidadId);

        if ($movilidad === null) {
            throw new MovilidadNoEncontradaException("No existe una movilidad con id {$movilidadId}.");
        }

        $activada = $this->movilidades->guardar($movilidad->activar());

        if ($activada->rutaId !== null) {
            $this->movilidades->desactivarHermanas($activada->rutaId, (int) $activada->id);
        }

        return MovilidadData::desdeEntidad($activada);
    }
}
