<?php

namespace Aim\Iam;

use Aim\Iam\Http\Middleware\CheckPermission;
use Aim\Iam\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class IamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/iam.php', 'iam');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/iam.php' => config_path('iam.php'),
        ], 'iam-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'iam-migrations');

        $this->publishes([
            __DIR__.'/../routes/iam.php' => base_path('routes/iam.php'),
        ], 'iam-routes');

        $this->loadRoutesFrom(__DIR__.'/../routes/iam.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Route::aliasMiddleware('role', CheckRole::class);
        Route::aliasMiddleware('permission', CheckPermission::class);
    }
}
