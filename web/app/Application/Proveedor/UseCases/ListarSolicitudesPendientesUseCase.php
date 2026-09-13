<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Proveedor\DTOs\SolicitudCambioZonaData;
use App\Domain\Proveedor\Entities\SolicitudCambioZona;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Models\User;

/**
 * Solicitudes de cambio de zona pendientes de aprobacion, con los nombres
 * ya resueltos para la bandeja del administrador.
 */
final class ListarSolicitudesPendientesUseCase
{
    public function __construct(
        private readonly SolicitudCambioZonaRepository $solicitudes,
        private readonly ProveedorRepository $proveedores,
        private readonly RutaRepository $rutas,
    ) {
    }

    /**
     * @return SolicitudCambioZonaData[]
     */
    public function ejecutar(): array
    {
        $pendientes = $this->solicitudes->listarPendientes();

        $nombresProveedor = [];
        foreach ($this->proveedores->listarTodos() as $proveedor) {
            $nombresProveedor[$proveedor->id] = (string) $proveedor->nombre;
        }

        $nombresRuta = [];
        foreach ($this->rutas->listarTodas() as $ruta) {
            $nombresRuta[$ruta->id] = $ruta->nombre;
        }

        $nombresUsuario = User::query()
            ->whereIn('id', array_unique(array_map(static fn (SolicitudCambioZona $s): int => $s->solicitadoPor, $pendientes)))
            ->pluck('name', 'id');

        return array_map(
            fn (SolicitudCambioZona $s): SolicitudCambioZonaData => new SolicitudCambioZonaData(
                id: (int) $s->id,
                proveedorId: $s->proveedorId,
                proveedorNombre: $nombresProveedor[$s->proveedorId] ?? 'Proveedor #'.$s->proveedorId,
                rutaActualNombre: $s->rutaActualId !== null ? ($nombresRuta[$s->rutaActualId] ?? null) : null,
                rutaSolicitadaNombre: $nombresRuta[$s->rutaSolicitadaId] ?? 'Ruta #'.$s->rutaSolicitadaId,
                fechaCambio: $s->fechaCambio->format('Y-m-d'),
                motivo: $s->motivo,
                estado: $s->estado->value,
                solicitadoPorNombre: $nombresUsuario[$s->solicitadoPor] ?? null,
                fechaRevision: $s->fechaRevision?->format('Y-m-d H:i:s'),
                observacionRevision: $s->observacionRevision,
            ),
            $pendientes,
        );
    }
}
