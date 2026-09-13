<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\MovilidadData;
use App\Domain\Movilidad\Exceptions\MovilidadNoEncontradaException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;

final class RenombrarCamionUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    public function ejecutar(int $movilidadId, string $nombre): MovilidadData
    {
        $movilidad = $this->movilidades->buscarPorId($movilidadId);

        if ($movilidad === null) {
            throw new MovilidadNoEncontradaException("No existe una movilidad con id {$movilidadId}.");
        }

        return MovilidadData::desdeEntidad($this->movilidades->guardar($movilidad->renombrar($nombre)));
    }
}
