<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Domain\Proveedor\Exceptions\SolicitudCambioZonaNoEncontradaException;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Domain\Shared\Contracts\TransactionManager;

/**
 * Aprobar es lo unico que mueve realmente al proveedor de ruta: marca la
 * solicitud como aprobada y reasigna su ruta en la misma transaccion,
 * reutilizando ReasignarRutaProveedorUseCase (el mismo que ya usa la
 * pantalla de edicion de proveedores). El acopiador de la nueva ruta lo vera
 * de inmediato porque el recorrido se arma leyendo proveedores.ruta_id.
 */
final class AprobarCambioZonaUseCase
{
    public function __construct(
        private readonly SolicitudCambioZonaRepository $solicitudes,
        private readonly ReasignarRutaProveedorUseCase $reasignarRuta,
        private readonly TransactionManager $transacciones,
    ) {
    }

    public function ejecutar(int $solicitudId, int $revisadoPor, ?string $observacion): void
    {
        $solicitud = $this->solicitudes->buscarPorId($solicitudId);

        if ($solicitud === null) {
            throw new SolicitudCambioZonaNoEncontradaException("No existe una solicitud con id {$solicitudId}.");
        }

        $this->transacciones->run(function () use ($solicitud, $revisadoPor, $observacion): void {
            $this->solicitudes->guardar($solicitud->aprobar($revisadoPor, $observacion));
            $this->reasignarRuta->ejecutar($solicitud->proveedorId, $solicitud->rutaSolicitadaId);
        });
    }
}
