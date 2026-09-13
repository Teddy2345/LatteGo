<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class MovilidadPolicy
{
    /** Ver las tarjetas de movilidades: acopiador en campo o quien ve reportes. */
    public function viewAny(User $user): bool
    {
        return $user->can('acopios.registrar') || $user->can('reportes.ver');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Crear/renombrar/quitar camiones y asignar rutas es planificacion, no trabajo de campo. */
    public function gestionar(User $user): bool
    {
        return $user->can('reportes.ver');
    }

    /** Registrar que un proveedor no entrego leche es una accion del acopiador en su recorrido. */
    public function registrarIncidencia(User $user): bool
    {
        return $user->can('acopios.registrar');
    }
}
