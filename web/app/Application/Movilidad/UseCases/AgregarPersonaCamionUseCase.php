<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Domain\Movilidad\Repositories\MovilidadRepository;

final class AgregarPersonaCamionUseCase
{
    public function __construct(
        private readonly MovilidadRepository $movilidades,
    ) {
    }

    public function ejecutar(int $movilidadId, string $nombre): void
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            return;
        }

        $this->movilidades->agregarPersona($movilidadId, $nombre);
    }
}
