<?php

declare(strict_types=1);

namespace App\Infrastructure\Inventario\Providers;

use App\Domain\Inventario\Repositories\MovimientoInventarioRepository;
use App\Domain\Inventario\Repositories\ProductoRepository;
use App\Infrastructure\Inventario\Models\MovimientoInventarioModel;
use App\Infrastructure\Inventario\Models\ProductoModel;
use App\Infrastructure\Inventario\Repositories\EloquentMovimientoInventarioRepository;
use App\Infrastructure\Inventario\Repositories\EloquentProductoRepository;
use App\Policies\InventarioPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class InventarioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProductoRepository::class, EloquentProductoRepository::class);
        $this->app->bind(MovimientoInventarioRepository::class, EloquentMovimientoInventarioRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(ProductoModel::class, InventarioPolicy::class);
        Gate::policy(MovimientoInventarioModel::class, InventarioPolicy::class);
    }
}
