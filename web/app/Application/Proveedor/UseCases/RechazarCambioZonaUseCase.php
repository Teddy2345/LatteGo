<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Domain\Proveedor\Exceptions\SolicitudCambioZonaNoEncontradaException;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;

final class RechazarCambioZonaUseCase
{
    public function __construct(
        private readonly SolicitudCambioZonaRepository $solicitudes,
    ) {
    }

    public function ejecutar(int $solicitudId, int $revisadoPor, ?string $observacion): void
    {
        $solicitud = $this->solicitudes->buscarPorId($solicitudId);

        if ($solicitud === null) {
            throw new SolicitudCambioZonaNoEncontradaException("No existe una solicitud con id {$solicitudId}.");
        }

        $this->solicitudes->guardar($solicitud->rechazar($revisadoPor, $observacion));
    }
}
