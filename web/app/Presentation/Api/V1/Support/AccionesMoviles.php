<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Support;

use App\Models\User;

/**
 * Traduce los permisos del usuario a la lista de acciones que la app movil
 * usa para decidir que modulos muestra en el inicio y en la barra inferior.
 *
 * "planta" habilita registrar transformaciones de leche en producto
 * terminado, "stock" ver las existencias y "ventas" registrar salidas.
 */
final class AccionesMoviles
{
    /**
     * @return string[]
     */
    public static function para(User $user): array
    {
        $acciones = [];

        if ($user->can('acopios.registrar')) {
            $acciones[] = 'acopio';
        }

        if ($user->can('reportes.ver')) {
            $acciones[] = 'consolidado';
            $acciones[] = 'auditoria';
        }

        if ($user->can('proveedores.ver')) {
            $acciones[] = 'proveedores';
        }

        if ($user->can('calidad.registrar')) {
            $acciones[] = 'calidad';
        }

        if ($user->can('produccion.registrar')) {
            $acciones[] = 'despachos';
            $acciones[] = 'planta';
        }

        if ($user->can('inventario.ver')) {
            $acciones[] = 'stock';
        }

        if ($user->can('ventas.registrar')) {
            $acciones[] = 'ventas';
        }

        if ($user->can('pagos.generar')) {
            $acciones[] = 'pagos';
        }

        return $acciones;
    }

    /**
     * Etiqueta corta que la app muestra bajo el nombre de la marca.
     */
    public static function perfil(User $user): string
    {
        $rol = $user->getRoleNames()->first();

        return $rol === null ? 'HUATA' : mb_strtoupper((string) $rol).' · HUATA';
    }
}
