<?php

declare(strict_types=1);

namespace App\Application\Movilidad\UseCases;

use App\Application\Movilidad\DTOs\RegistrarNoEntregaData;
use App\Domain\Movilidad\Entities\IncidenciaRecorrido;
use App\Domain\Movilidad\Repositories\IncidenciaRecorridoRepository;
use DateTimeImmutable;

final class RegistrarNoEntregaUseCase
{
    public function __construct(
        private readonly IncidenciaRecorridoRepository $incidencias,
    ) {
    }

    public function ejecutar(RegistrarNoEntregaData $datos): void
    {
        $this->incidencias->guardar(new IncidenciaRecorrido(
            id: null,
            proveedorId: $datos->proveedorId,
            movilidadId: $datos->movilidadId,
            fecha: new DateTimeImmutable($datos->fecha),
            motivo: $datos->motivo,
            registradoPor: $datos->registradoPor,
        ));
    }
}
