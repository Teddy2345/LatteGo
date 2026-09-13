<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class ProveedorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('proveedores.ver');
    }

    public function view(User $user): bool
    {
        return $user->can('proveedores.ver');
    }

    public function create(User $user): bool
    {
        return $user->can('proveedores.crear');
    }

    public function update(User $user): bool
    {
        return $user->can('proveedores.editar');
    }

    public function delete(User $user): bool
    {
        return $user->can('proveedores.eliminar');
    }
}
