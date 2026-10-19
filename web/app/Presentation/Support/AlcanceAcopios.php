<?php

declare(strict_types=1);

namespace App\Presentation\Support;

use App\Models\User;

/**
 * Hasta donde ve acopios cada usuario. Quien ve reportes ve los de toda la
 * planta; el acopiador, solo los que el mismo registro: cada acopio lleva el
 * proveedor, los litros y el punto GPS de quien lo tomo, y no le corresponde
 * ver los recorridos de sus compañeros.
 */
final class AlcanceAcopios
{
    /** Id del acopiador al que hay que limitar la vista, o null si ve todos. */
    public static function soloDe(User $usuario): ?int
    {
        return $usuario->can('reportes.ver') ? null : (int) $usuario->id;
    }

    public static function puedeVer(User $usuario, ?int $acopiadorId): bool
    {
        $soloDe = self::soloDe($usuario);

        return $soloDe === null || $acopiadorId === $soloDe;
    }
}
