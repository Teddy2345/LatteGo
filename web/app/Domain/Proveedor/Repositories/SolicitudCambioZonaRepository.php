<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Repositories;

use App\Domain\Proveedor\Entities\SolicitudCambioZona;

interface SolicitudCambioZonaRepository
{
    public function guardar(SolicitudCambioZona $solicitud): SolicitudCambioZona;

    public function buscarPorId(int $id): ?SolicitudCambioZona;

    public function existePendientePara(int $proveedorId): bool;

    /**
     * @return SolicitudCambioZona[]
     */
    public function listarPendientes(): array;

    /**
     * Historial completo del proveedor, mas reciente primero. Nunca se borra.
     *
     * @return SolicitudCambioZona[]
     */
    public function listarPorProveedor(int $proveedorId): array;

    /**
     * Solicitudes pendientes indexadas por proveedor_id, para marcar el
     * recorrido del acopiador sin una consulta por proveedor.
     *
     * @return array<int, SolicitudCambioZona>
     */
    public function listarPendientesIndexadasPorProveedor(): array;
}
