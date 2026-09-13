<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Los pedidos de la tienda web los atiende quien comercializa; quien solo
 * ve reportes tambien puede consultarlos. Crear un pedido es publico (lo
 * hace un cliente sin cuenta) y no pasa por esta policy.
 */
final class PedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ventas.registrar') || $user->can('reportes.ver') || $user->can('pedidos.repartir');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function revisar(User $user): bool
    {
        return $user->can('ventas.registrar');
    }

    /** Asignar quien reparte es parte de gestionar el pedido, igual que confirmarlo o cancelarlo. */
    public function asignarRepartidor(User $user): bool
    {
        return $user->can('ventas.registrar');
    }

    /** Marca la entrega y el cobro quien reparte en el momento; Ventas puede hacerlo en su nombre si hace falta. */
    public function entregar(User $user): bool
    {
        return $user->can('pedidos.repartir') || $user->can('ventas.registrar');
    }
}
