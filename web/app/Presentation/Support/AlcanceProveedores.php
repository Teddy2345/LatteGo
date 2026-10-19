<?php

declare(strict_types=1);

namespace App\Presentation\Support;

use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Domain\Proveedor\ValueObjects\EstadoSolicitudCambioZona;
use App\Models\User;
use DateTimeImmutable;

/**
 * A que proveedores atiende cada usuario. Cada camion, la motocarga y la
 * recepcion en planta tienen su propia ruta y su acopiador titular: el
 * acopiador solo ve y registra a los proveedores de esa ruta. Quien ve
 * reportes de toda la planta los atiende a todos.
 */
final class AlcanceProveedores
{
    /** Cuanto puede tardar en llegar con señal un acopio tomado antes de un traslado. */
    private const DIAS_GRACIA_TRASLADO = 7;

    /** Id de la ruta a la que se limita, 0 si no tiene movilidad (ninguna), o null si ve todas. */
    public static function rutaPermitida(User $usuario): ?int
    {
        if ($usuario->can('reportes.ver')) {
            return null;
        }

        return app(MovilidadRepository::class)->buscarPorUsuario((int) $usuario->id)?->rutaId ?? 0;
    }

    /**
     * @param  string|null  $fechaAcopio  Solo para acopios que llegan desde el telefono: si el
     *                                    proveedor se traslado de ruta mientras el acopio esperaba
     *                                    señal, sigue siendo de quien lo tomo en su recorrido.
     */
    public static function puedeAtender(User $usuario, int $proveedorId, ?int $rutaDelProveedor, ?string $fechaAcopio = null): bool
    {
        $permitida = self::rutaPermitida($usuario);

        if ($permitida === null || ($permitida !== 0 && $rutaDelProveedor === $permitida)) {
            return true;
        }

        return $permitida !== 0 && $fechaAcopio !== null && self::salioDeLaRutaDespuesDe($proveedorId, $permitida, $fechaAcopio);
    }

    /**
     * El traslado tuvo que aprobarse hace poco y despues del dia del acopio:
     * no basta con que el proveedor haya estado alguna vez en esa ruta.
     */
    private static function salioDeLaRutaDespuesDe(int $proveedorId, int $rutaId, string $fechaAcopio): bool
    {
        $fecha = (new DateTimeImmutable($fechaAcopio))->format('Y-m-d');
        $limite = (new DateTimeImmutable())->modify('-'.self::DIAS_GRACIA_TRASLADO.' days');

        foreach (app(SolicitudCambioZonaRepository::class)->listarPorProveedor($proveedorId) as $solicitud) {
            if ($solicitud->estado === EstadoSolicitudCambioZona::Aprobada
                && $solicitud->rutaActualId === $rutaId
                && $solicitud->fechaRevision !== null
                && $solicitud->fechaRevision >= $limite
                && $solicitud->fechaRevision->format('Y-m-d') >= $fecha) {
                return true;
            }
        }

        return false;
    }
}
