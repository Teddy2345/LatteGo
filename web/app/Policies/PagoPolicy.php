<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class PagoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reportes.ver') || $user->can('pagos.generar');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function generarPlanilla(User $user): bool
    {
        return $user->can('pagos.generar');
    }
}
