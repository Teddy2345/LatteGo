<?php

declare(strict_types=1);

namespace App\Application\Proveedor\UseCases;

use App\Application\Notificacion\DTOs\RegistrarNotificacionData;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Application\Proveedor\DTOs\CrearSolicitudCambioZonaData;
use App\Domain\Notificacion\ValueObjects\NivelNotificacion;
use App\Domain\Proveedor\Entities\SolicitudCambioZona;
use App\Domain\Proveedor\Exceptions\ProveedorNoEncontradoException;
use App\Domain\Proveedor\Exceptions\SolicitudDuplicadaException;
use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Domain\Shared\Contracts\TransactionManager;
use DateTimeImmutable;

/**
 * El acopiador registra que un proveedor se trasladara de zona. La ruta
 * actual queda fijada al momento de la solicitud (aunque el proveedor
 * vuelva a moverse despues); el cambio real solo ocurre si un administrador
 * la aprueba.
 *
 * La solicitud se avisa a quien supervisa la planta: el acopiador la pide
 * desde el campo y sin el aviso quedaria esperando en una bandeja que nadie
 * sabe que tiene algo. Guardarla y avisar ocurren en una sola transaccion.
 */
final class CrearSolicitudCambioZonaUseCase
{
    public function __construct(
        private readonly SolicitudCambioZonaRepository $solicitudes,
        private readonly ProveedorRepository $proveedores,
        private readonly RutaRepository $rutas,
        private readonly RegistrarNotificacionUseCase $notificar,
        private readonly TransactionManager $transacciones,
    ) {
    }

    public function ejecutar(CrearSolicitudCambioZonaData $datos): SolicitudCambioZona
    {
        $proveedor = $this->proveedores->buscarPorId($datos->proveedorId);

        if ($proveedor === null) {
            throw new ProveedorNoEncontradoException("No existe un proveedor con id {$datos->proveedorId}.");
        }

        if ($this->solicitudes->existePendientePara($datos->proveedorId)) {
            throw new SolicitudDuplicadaException(
                'Este proveedor ya tiene una solicitud de cambio de zona pendiente de revision.'
            );
        }

        return $this->transacciones->run(function () use ($datos, $proveedor): SolicitudCambioZona {
            $solicitud = $this->solicitudes->guardar(SolicitudCambioZona::crear(
                proveedorId: $datos->proveedorId,
                rutaActualId: $proveedor->rutaId,
                rutaSolicitadaId: $datos->rutaSolicitadaId,
                fechaCambio: new DateTimeImmutable($datos->fechaCambio),
                motivo: $datos->motivo,
                solicitadoPor: $datos->solicitadoPor,
            ));

            $this->notificarSolicitud($solicitud, (string) $proveedor->nombre);

            return $solicitud;
        });
    }

    private function notificarSolicitud(SolicitudCambioZona $solicitud, string $nombreProveedor): void
    {
        $nombresRuta = [];
        foreach ($this->rutas->listarTodas() as $ruta) {
            $nombresRuta[$ruta->id] = $ruta->nombre;
        }

        $zonaActual = $solicitud->rutaActualId === null
            ? 'Sin zona asignada'
            : ($nombresRuta[$solicitud->rutaActualId] ?? 'Sin zona asignada');
        $zonaSolicitada = $nombresRuta[$solicitud->rutaSolicitadaId] ?? 'Zona #'.$solicitud->rutaSolicitadaId;

        $this->notificar->ejecutar(new RegistrarNotificacionData(
            tipo: 'cambio_zona_solicitado',
            titulo: 'Cambio de zona por aprobar',
            mensaje: "{$nombreProveedor} pide pasar de {$zonaActual} a {$zonaSolicitada}.",
            nivel: NivelNotificacion::Advertencia,
            datos: array_filter([
                'Proveedor' => $nombreProveedor,
                'Zona actual' => $zonaActual,
                'Zona solicitada' => $zonaSolicitada,
                'Fecha del cambio' => $solicitud->fechaCambio->format('Y-m-d'),
                'Motivo' => $solicitud->motivo,
            ], static fn (?string $valor): bool => $valor !== null),
        ));
    }
}
