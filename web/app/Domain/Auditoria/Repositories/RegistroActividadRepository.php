<?php

declare(strict_types=1);

namespace App\Domain\Auditoria\Repositories;

use App\Domain\Auditoria\Entities\RegistroActividad;

interface RegistroActividadRepository
{
    /**
     * Devuelve la actividad mas reciente primero.
     *
     * @return RegistroActividad[]
     */
    public function listarRecientes(int $limite): array;
}
