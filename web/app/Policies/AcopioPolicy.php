<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class AcopioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reportes.ver') || $user->can('acopios.registrar');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('acopios.registrar');
    }

    public function sincronizar(User $user): bool
    {
        return $user->can('acopios.sincronizar');
    }

    /**
     * El consolidado por zonas es un reporte de planta, no una vista de
     * campo: lo consulta quien supervisa la recepcion del dia.
     */
    public function consolidar(User $user): bool
    {
        return $user->can('reportes.ver');
    }
}
