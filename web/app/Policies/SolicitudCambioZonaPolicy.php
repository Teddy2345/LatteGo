<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class SolicitudCambioZonaPolicy
{
    /** Quien acopia en campo es quien detecta que un proveedor se traslada. */
    public function create(User $user): bool
    {
        return $user->can('acopios.registrar');
    }

    /** La bandeja de aprobacion es la misma autoridad que ya reasigna rutas. */
    public function viewAny(User $user): bool
    {
        return $user->can('proveedores.editar');
    }

    public function revisar(User $user): bool
    {
        return $user->can('proveedores.editar');
    }
}
