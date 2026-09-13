<?php

declare(strict_types=1);

namespace App\Infrastructure\Acopio\Providers;

use App\Domain\Acopio\Repositories\AcopioRepository;
use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Acopio\Repositories\EloquentAcopioRepository;
use App\Policies\AcopioPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AcopioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AcopioRepository::class, EloquentAcopioRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(AcopioModel::class, AcopioPolicy::class);
    }
}
