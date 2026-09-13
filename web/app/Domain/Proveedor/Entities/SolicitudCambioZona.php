<?php

declare(strict_types=1);

namespace App\Domain\Proveedor\Entities;

use App\Domain\Proveedor\Exceptions\SolicitudYaRevisadaException;
use App\Domain\Proveedor\ValueObjects\EstadoSolicitudCambioZona;
use DateTimeImmutable;

/**
 * El acopiador registra que un proveedor se trasladara de zona; el cambio
 * no se aplica solo. Queda pendiente hasta que un administrador la aprueba
 * o la rechaza. Aprobarla es lo unico que mueve al proveedor de ruta
 * (via ReasignarRutaProveedorUseCase); rechazarla no toca nada.
 *
 * Nunca se borra: es, a la vez, la solicitud y el historial de cambios de
 * zona del proveedor.
 */
final class SolicitudCambioZona
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $proveedorId,
        public readonly ?int $rutaActualId,
        public readonly int $rutaSolicitadaId,
        public readonly DateTimeImmutable $fechaCambio,
        public readonly ?string $motivo,
        public readonly EstadoSolicitudCambioZona $estado,
        public readonly int $solicitadoPor,
        public readonly ?int $revisadoPor,
        public readonly ?DateTimeImmutable $fechaRevision,
        public readonly ?string $observacionRevision,
    ) {
    }

    public static function crear(
        int $proveedorId,
        ?int $rutaActualId,
        int $rutaSolicitadaId,
        DateTimeImmutable $fechaCambio,
        ?string $motivo,
        int $solicitadoPor,
    ): self {
        return new self(
            id: null,
            proveedorId: $proveedorId,
            rutaActualId: $rutaActualId,
            rutaSolicitadaId: $rutaSolicitadaId,
            fechaCambio: $fechaCambio,
            motivo: $motivo,
            estado: EstadoSolicitudCambioZona::Pendiente,
            solicitadoPor: $solicitadoPor,
            revisadoPor: null,
            fechaRevision: null,
            observacionRevision: null,
        );
    }

    public static function reconstituir(
        int $id,
        int $proveedorId,
        ?int $rutaActualId,
        int $rutaSolicitadaId,
        DateTimeImmutable $fechaCambio,
        ?string $motivo,
        EstadoSolicitudCambioZona $estado,
        int $solicitadoPor,
        ?int $revisadoPor,
        ?DateTimeImmutable $fechaRevision,
        ?string $observacionRevision,
    ): self {
        return new self(
            $id,
            $proveedorId,
            $rutaActualId,
            $rutaSolicitadaId,
            $fechaCambio,
            $motivo,
            $estado,
            $solicitadoPor,
            $revisadoPor,
            $fechaRevision,
            $observacionRevision,
        );
    }

    public function aprobar(int $revisadoPor, ?string $observacion): self
    {
        $this->exigirPendiente();

        return new self(
            $this->id,
            $this->proveedorId,
            $this->rutaActualId,
            $this->rutaSolicitadaId,
            $this->fechaCambio,
            $this->motivo,
            EstadoSolicitudCambioZona::Aprobada,
            $this->solicitadoPor,
            $revisadoPor,
            new DateTimeImmutable(),
            $observacion,
        );
    }

    public function rechazar(int $revisadoPor, ?string $observacion): self
    {
        $this->exigirPendiente();

        return new self(
            $this->id,
            $this->proveedorId,
            $this->rutaActualId,
            $this->rutaSolicitadaId,
            $this->fechaCambio,
            $this->motivo,
            EstadoSolicitudCambioZona::Rechazada,
            $this->solicitadoPor,
            $revisadoPor,
            new DateTimeImmutable(),
            $observacion,
        );
    }

    private function exigirPendiente(): void
    {
        if ($this->estado !== EstadoSolicitudCambioZona::Pendiente) {
            throw new SolicitudYaRevisadaException('Esta solicitud ya fue revisada.');
        }
    }
}
