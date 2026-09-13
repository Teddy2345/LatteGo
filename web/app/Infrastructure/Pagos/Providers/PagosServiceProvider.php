<?php

declare(strict_types=1);

namespace App\Infrastructure\Pagos\Providers;

use App\Domain\Pagos\Repositories\PagoRepository;
use App\Infrastructure\Pagos\Models\PagoModel;
use App\Infrastructure\Pagos\Repositories\EloquentPagoRepository;
use App\Policies\PagoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PagosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PagoRepository::class, EloquentPagoRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(PagoModel::class, PagoPolicy::class);
    }
}
