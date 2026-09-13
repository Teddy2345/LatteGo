<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class InventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventario.ver');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Registrar una transformacion es trabajo de planta. */
    public function transformar(User $user): bool
    {
        return $user->can('produccion.registrar');
    }

    /**
     * Dar de alta insumos/productos y registrar sus entradas y salidas es
     * trabajo de almacen, distinto de solo consultar el stock
     * (inventario.ver) que ya tienen produccion, ventas y supervisor.
     */
    public function gestionarCatalogo(User $user): bool
    {
        return $user->can('inventario.registrar');
    }

    public function registrarEntrada(User $user): bool
    {
        return $user->can('inventario.registrar');
    }

    public function registrarSalida(User $user): bool
    {
        return $user->can('inventario.registrar');
    }

    /**
     * El historial de ventas expone precios, clientes y recaudacion, asi que
     * no basta con poder ver el almacen: se reserva a quien comercializa o
     * consulta reportes.
     */
    public function verVentas(User $user): bool
    {
        return $user->can('ventas.registrar') || $user->can('reportes.ver');
    }

    public function vender(User $user): bool
    {
        return $user->can('ventas.registrar');
    }
}
