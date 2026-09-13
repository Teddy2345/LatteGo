<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Domain\Movilidad\Repositories\MovilidadRepository;

final class QuitarPersonaCamionUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    public function ejecutar(int $personaId): void
    {
        $this->movilidades->quitarPersona($personaId);
    }
}
