<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Stancl\Tenancy\Events\TenantSaved;
use App\Listeners\RunTenantMigrations;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // NOTE: Listener deshabilitado - las migraciones se ejecutan manualmente en TenantCreate
        // para evitar conflictos con el ID del tenant durante el proceso de guardado
        // TenantSaved::class => [
        //     RunTenantMigrations::class,
        // ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
