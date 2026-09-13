<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Shared\Contracts\TransactionManager;
use App\Infrastructure\Shared\Services\DatabaseTransactionManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TransactionManager::class, DatabaseTransactionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
