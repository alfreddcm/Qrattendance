<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom multi-model authentication provider
        \Illuminate\Support\Facades\Auth::provider('multi-model', function ($app, array $config) {
            return new \App\Auth\MultiModelUserProvider($app['hash'], $config['model']);
        });
    }
}
