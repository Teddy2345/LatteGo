<?php

declare(strict_types=1);

namespace App\Infrastructure\Comprobantes\Providers;

use App\Domain\Comprobantes\Repositories\ComprobanteRepository;
use App\Infrastructure\Comprobantes\Repositories\EloquentComprobanteRepository;
use Illuminate\Support\ServiceProvider;

class ComprobantesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ComprobanteRepository::class, EloquentComprobanteRepository::class);
    }
}
