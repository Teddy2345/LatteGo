<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class CalidadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reportes.ver') || $user->can('calidad.registrar');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('calidad.registrar');
    }
}
