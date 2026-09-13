<?php

declare(strict_types=1);

namespace App\Application\Calidad\UseCases;

use App\Application\Calidad\DTOs\CalidadData;
use App\Domain\Calidad\Entities\Calidad;
use App\Domain\Calidad\Repositories\CalidadRepository;

final class ListarCalidadUseCase
{
    public function __construct(
        private readonly CalidadRepository $analisis,
    ) {
    }

    /**
     * @return CalidadData[]
     */
    public function ejecutar(): array
    {
        return array_map(
            static fn (Calidad $calidad): CalidadData => CalidadData::desdeEntidad($calidad),
            $this->analisis->listarTodos(),
        );
    }
}
