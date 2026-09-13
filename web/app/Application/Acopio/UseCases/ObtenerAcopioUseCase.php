<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Domain\Acopio\Exceptions\AcopioNoEncontradoException;
use App\Domain\Acopio\Repositories\AcopioRepository;

final class ObtenerAcopioUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
    ) {
    }

    public function ejecutar(int $id): AcopioData
    {
        $acopio = $this->acopios->buscarPorId($id);

        if ($acopio === null) {
            throw new AcopioNoEncontradoException("No existe un acopio con id {$id}.");
        }

        return AcopioData::desdeEntidad($acopio);
    }
}
