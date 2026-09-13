<?php

declare(strict_types=1);

namespace App\Application\Acopio\UseCases;

use App\Application\Acopio\DTOs\AcopioData;
use App\Domain\Acopio\Entities\Acopio;
use App\Domain\Acopio\Repositories\AcopioRepository;

final class ListarAcopiosUseCase
{
    public function __construct(
        private readonly AcopioRepository $acopios,
    ) {
    }

    /**
     * @return AcopioData[]
     */
    public function ejecutar(): array
    {
        return array_map(
            static fn (Acopio $acopio): AcopioData => AcopioData::desdeEntidad($acopio),
            $this->acopios->listarTodos(),
        );
    }
}
