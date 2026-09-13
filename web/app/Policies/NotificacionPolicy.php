<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Las notificaciones de calidad exponen resultados y sanciones de un
 * proveedor especifico: informacion de supervision, no de campo. Se
 * restringen a quien ya tiene la vista de reportes (admin y supervisor),
 * en vez de a todos los roles que usan el sistema.
 */
final class NotificacionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reportes.ver');
    }
}
