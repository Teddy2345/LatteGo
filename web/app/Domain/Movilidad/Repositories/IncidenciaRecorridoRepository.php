<?php

declare(strict_types=1);

namespace App\Domain\Movilidad\Repositories;

use App\Domain\Movilidad\Entities\IncidenciaRecorrido;
use DateTimeImmutable;

interface IncidenciaRecorridoRepository
{
    public function guardar(IncidenciaRecorrido $incidencia): IncidenciaRecorrido;

    /**
     * Proveedores con una incidencia de "no entrego" en esa fecha, para la
     * movilidad indicada. Indexado por proveedor_id para lectura rapida.
     *
     * @return array<int, IncidenciaRecorrido>
     */
    public function listarPorMovilidadYFecha(int $movilidadId, DateTimeImmutable $fecha): array;
}
