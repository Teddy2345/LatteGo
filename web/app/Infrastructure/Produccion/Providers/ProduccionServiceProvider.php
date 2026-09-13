<?php

declare(strict_types=1);

namespace App\Infrastructure\Produccion\Providers;

use App\Domain\Produccion\Repositories\DespachoRepository;
use App\Domain\Produccion\Repositories\ProduccionRepository;
use App\Infrastructure\Produccion\Models\DespachoModel;
use App\Infrastructure\Produccion\Models\ProduccionModel;
use App\Infrastructure\Produccion\Repositories\EloquentDespachoRepository;
use App\Infrastructure\Produccion\Repositories\EloquentProduccionRepository;
use App\Policies\ProduccionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProduccionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProduccionRepository::class, EloquentProduccionRepository::class);
        $this->app->bind(DespachoRepository::class, EloquentDespachoRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(ProduccionModel::class, ProduccionPolicy::class);
        Gate::policy(DespachoModel::class, ProduccionPolicy::class);
    }
}
