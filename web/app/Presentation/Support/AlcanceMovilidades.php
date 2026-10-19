<?php

declare(strict_types=1);

namespace App\Presentation\Support;

use App\Domain\Movilidad\Entities\Movilidad;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Models\User;

/**
 * Que movilidades maneja cada usuario. El acopiador solo abre aquella de la
 * que es titular —su camion o la motocarga— porque de ahi cuelgan la ruta,
 * los telefonos de sus proveedores y el registro de acopio; quien ve
 * reportes de toda la planta las abre todas.
 *
 * Vive aqui, junto a [AlcanceAcopios] y [AlcanceProveedores], para que la
 * regla y sus avisos se escriban una sola vez: Acopios y Movilidades la
 * consultan desde sus pantallas en lugar de repetirla cada una.
 */
final class AlcanceMovilidades
{
    public const AJENA = 'Esta movilidad no te fue asignada.';

    public const FUERA_DE_RUTA = 'Ese proveedor no pertenece a esta ruta.';

    /** Id del acopiador al que hay que limitar la vista, o null si las ve todas. */
    public static function soloDe(User $usuario): ?int
    {
        return $usuario->can('reportes.ver') ? null : (int) $usuario->id;
    }

    public static function puedeVer(User $usuario, ?int $titularId): bool
    {
        $soloDe = self::soloDe($usuario);

        return $soloDe === null || $titularId === $soloDe;
    }

    /**
     * La movilidad que se va a manejar, comprobando que sea suya: 404 si no
     * existe y 403 si es de otro acopiador. El id llega por la URL, asi que
     * se revisa siempre aunque la pantalla no lo muestre.
     */
    public static function propia(User $usuario, int $movilidadId): Movilidad
    {
        $movilidad = app(MovilidadRepository::class)->buscarPorId($movilidadId);

        abort_if($movilidad === null, 404);
        abort_unless(self::puedeVer($usuario, $movilidad->usuarioId), 403, self::AJENA);

        return $movilidad;
    }

    /**
     * El proveedor tambien llega del navegador: si no es del recorrido de
     * esa movilidad se podria acopiar leche de otra ruta a su nombre, o
     * marcarle un "no entrego" y pedir su traslado sin atenderlo nunca.
     */
    public static function asegurarProveedorDelRecorrido(Movilidad $movilidad, ?int $rutaDelProveedor): void
    {
        abort_if($rutaDelProveedor === null || $rutaDelProveedor !== $movilidad->rutaId, 403, self::FUERA_DE_RUTA);
    }
}
