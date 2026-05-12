<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
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
        // Configure Scramble API docs if the package is installed
        if (class_exists(Scramble::class)) {
            // Expose UI at /docs and OpenAPI JSON at /docs/openapi.json
            Scramble::configure()->expose(ui: '/docs', document: '/docs/openapi.json');
        }
    }
}
