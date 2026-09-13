<?php

declare(strict_types=1);

namespace App\Infrastructure\Proveedor\Providers;

use App\Domain\Proveedor\Repositories\ProveedorRepository;
use App\Domain\Proveedor\Repositories\RutaRepository;
use App\Domain\Proveedor\Repositories\SolicitudCambioZonaRepository;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Infrastructure\Proveedor\Models\SolicitudCambioZonaModel;
use App\Infrastructure\Proveedor\Repositories\EloquentProveedorRepository;
use App\Infrastructure\Proveedor\Repositories\EloquentRutaRepository;
use App\Infrastructure\Proveedor\Repositories\EloquentSolicitudCambioZonaRepository;
use App\Policies\ProveedorPolicy;
use App\Policies\SolicitudCambioZonaPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProveedorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProveedorRepository::class, EloquentProveedorRepository::class);
        $this->app->bind(RutaRepository::class, EloquentRutaRepository::class);
        $this->app->bind(SolicitudCambioZonaRepository::class, EloquentSolicitudCambioZonaRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(ProveedorModel::class, ProveedorPolicy::class);
        Gate::policy(SolicitudCambioZonaModel::class, SolicitudCambioZonaPolicy::class);
    }
}
