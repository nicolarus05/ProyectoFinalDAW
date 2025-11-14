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
        // Compartir $errors con todas las vistas para compatibilidad con multi-tenancy
        view()->composer('*', function ($view) {
            $errors = session()->get('errors', new \Illuminate\Support\ViewErrorBag());
            $view->with('errors', $errors);
        });
    }
}
