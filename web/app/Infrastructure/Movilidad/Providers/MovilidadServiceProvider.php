<?php

declare(strict_types=1);

namespace App\Infrastructure\Movilidad\Providers;

use App\Domain\Movilidad\Repositories\IncidenciaRecorridoRepository;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Infrastructure\Movilidad\Models\IncidenciaRecorridoModel;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use App\Infrastructure\Movilidad\Repositories\EloquentIncidenciaRecorridoRepository;
use App\Infrastructure\Movilidad\Repositories\EloquentMovilidadRepository;
use App\Policies\MovilidadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class MovilidadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MovilidadRepository::class, EloquentMovilidadRepository::class);
        $this->app->bind(IncidenciaRecorridoRepository::class, EloquentIncidenciaRecorridoRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(MovilidadModel::class, MovilidadPolicy::class);
        Gate::policy(IncidenciaRecorridoModel::class, MovilidadPolicy::class);
    }
}
