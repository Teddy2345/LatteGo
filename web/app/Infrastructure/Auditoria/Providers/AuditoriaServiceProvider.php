<?php

declare(strict_types=1);

namespace App\Infrastructure\Auditoria\Providers;

use App\Domain\Auditoria\Repositories\RegistroActividadRepository;
use App\Infrastructure\Auditoria\Repositories\EloquentRegistroActividadRepository;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuditoriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RegistroActividadRepository::class, EloquentRegistroActividadRepository::class);
    }

    public function boot(): void
    {
        // La auditoria no tiene modelo propio de dominio, asi que se autoriza
        // con un Gate en lugar de una Policy.
        Gate::define('auditoria.ver', static fn (User $user): bool => $user->can('reportes.ver'));
    }
}
