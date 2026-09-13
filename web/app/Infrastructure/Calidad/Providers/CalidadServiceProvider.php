<?php

declare(strict_types=1);

namespace App\Infrastructure\Calidad\Providers;

use App\Domain\Calidad\Contracts\OcrProvider;
use App\Domain\Calidad\Repositories\CalidadRepository;
use App\Infrastructure\Calidad\Models\CalidadModel;
use App\Infrastructure\Calidad\Repositories\EloquentCalidadRepository;
use App\Infrastructure\Calidad\Services\NullOcrProvider;
use App\Policies\CalidadPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CalidadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CalidadRepository::class, EloquentCalidadRepository::class);

        // Sin proveedor de OCR conectado todavia: ver NullOcrProvider.
        $this->app->bind(OcrProvider::class, NullOcrProvider::class);
    }

    public function boot(): void
    {
        Gate::policy(CalidadModel::class, CalidadPolicy::class);
    }
}
