<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class ProduccionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reportes.ver') || $user->can('produccion.registrar');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('produccion.registrar');
    }
}
