<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class SolicitudCambioZonaPolicy
{
    /**
     * El traslado lo pide el propio proveedor desde su app: es quien sabe
     * que se muda y a donde. El acopiador ya no lo pide por el, para que
     * nadie mueva de zona a un ganadero sin que este lo haya pedido.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('proveedor');
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
