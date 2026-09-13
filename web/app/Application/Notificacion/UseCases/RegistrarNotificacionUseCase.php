<?php

declare(strict_types=1);

namespace App\Application\Notificacion\UseCases;

use App\Application\Notificacion\DTOs\RegistrarNotificacionData;
use App\Domain\Notificacion\Entities\Notificacion;
use App\Domain\Notificacion\Repositories\NotificacionRepository;

/**
 * Punto de entrada generico para crear una alerta. Lo usan otras features
 * (Calidad hoy; Almacen/Ventas/Sincronizacion mas adelante) sin acoplarse
 * a como se guarda o se muestra una notificacion.
 */
final class RegistrarNotificacionUseCase
{
    public function __construct(
        private readonly NotificacionRepository $notificaciones,
    ) {
    }

    public function ejecutar(RegistrarNotificacionData $datos): void
    {
        $this->notificaciones->guardar(new Notificacion(
            id: null,
            tipo: $datos->tipo,
            titulo: $datos->titulo,
            mensaje: $datos->mensaje,
            nivel: $datos->nivel,
            datos: $datos->datos,
            creadaEn: null,
        ));
    }
}
