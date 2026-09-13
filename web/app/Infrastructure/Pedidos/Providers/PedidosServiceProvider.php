<?php

declare(strict_types=1);

namespace App\Infrastructure\Pedidos\Providers;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Infrastructure\Pedidos\Models\PedidoModel;
use App\Infrastructure\Pedidos\Repositories\EloquentPedidoRepository;
use App\Policies\PedidoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PedidosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PedidoRepository::class, EloquentPedidoRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(PedidoModel::class, PedidoPolicy::class);
    }
}
