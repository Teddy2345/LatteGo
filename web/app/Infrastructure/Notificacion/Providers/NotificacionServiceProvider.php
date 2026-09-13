<?php

declare(strict_types=1);

namespace App\Infrastructure\Notificacion\Providers;

use App\Domain\Notificacion\Repositories\NotificacionRepository;
use App\Infrastructure\Notificacion\Models\NotificacionModel;
use App\Infrastructure\Notificacion\Repositories\EloquentNotificacionRepository;
use App\Policies\NotificacionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NotificacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificacionRepository::class, EloquentNotificacionRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(NotificacionModel::class, NotificacionPolicy::class);
    }
}
