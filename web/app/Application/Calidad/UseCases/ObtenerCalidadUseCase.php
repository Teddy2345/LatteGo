<?php

declare(strict_types=1);

namespace App\Application\Calidad\UseCases;

use App\Application\Calidad\DTOs\CalidadData;
use App\Domain\Calidad\Exceptions\CalidadNoEncontradaException;
use App\Domain\Calidad\Repositories\CalidadRepository;

final class ObtenerCalidadUseCase
{
    public function __construct(
        private readonly CalidadRepository $analisis,
    ) {
    }

    public function ejecutar(int $id): CalidadData
    {
        $calidad = $this->analisis->buscarPorId($id);

        if ($calidad === null) {
            throw new CalidadNoEncontradaException("No existe un analisis de calidad con id {$id}.");
        }

        return CalidadData::desdeEntidad($calidad);
    }
}
