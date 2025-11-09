<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Stancl\Tenancy\Events\TenancyEnded;

class TenantStorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar storage cuando se inicializa el tenant
        $this->app['events']->listen(TenancyBootstrapped::class, function (TenancyBootstrapped $event) {
            $this->configureTenantStorage($event->tenancy->tenant->getTenantKey());
        });

        // Restaurar storage cuando termina el contexto del tenant
        $this->app['events']->listen(TenancyEnded::class, function () {
            $this->restoreCentralStorage();
        });
    }

    /**
     * Configurar los discos de storage para el tenant actual
     */
    protected function configureTenantStorage(string $tenantId): void
    {
        // Configurar disco 'tenant' (privado)
        config([
            'filesystems.disks.tenant.root' => storage_path("app/tenants/{$tenantId}/private"),
        ]);

        // Configurar disco 'tenant_public' (público)
        config([
            'filesystems.disks.tenant_public.root' => storage_path("app/tenants/{$tenantId}/public"),
            'filesystems.disks.tenant_public.url' => config('app.url') . "/storage/tenants/{$tenantId}",
        ]);

        // Configurar disco S3 tenant (si se usa)
        if (config('filesystems.disks.s3_tenant')) {
            config([
                'filesystems.disks.s3_tenant.root' => "tenants/{$tenantId}",
            ]);
        }

        // Purgar instancias en caché de Storage para que tomen la nueva configuración
        Storage::forgetDisk('tenant');
        Storage::forgetDisk('tenant_public');
        Storage::forgetDisk('s3_tenant');

        // Crear directorios si no existen
        $this->ensureTenantDirectoriesExist($tenantId);
    }

    /**
     * Restaurar configuración de storage central
     */
    protected function restoreCentralStorage(): void
    {
        config([
            'filesystems.disks.tenant.root' => storage_path('app/tenants/%tenant_id%/private'),
            'filesystems.disks.tenant_public.root' => storage_path('app/tenants/%tenant_id%/public'),
            'filesystems.disks.tenant_public.url' => config('app.url') . '/storage/tenants/%tenant_id%',
        ]);

        Storage::forgetDisk('tenant');
        Storage::forgetDisk('tenant_public');
        Storage::forgetDisk('s3_tenant');
    }

    /**
     * Asegurar que los directorios del tenant existen
     */
    protected function ensureTenantDirectoriesExist(string $tenantId): void
    {
        $directories = [
            storage_path("app/tenants/{$tenantId}/private"),
            storage_path("app/tenants/{$tenantId}/public"),
            storage_path("app/tenants/{$tenantId}/public/productos"),
            storage_path("app/tenants/{$tenantId}/public/perfiles"),
            storage_path("app/tenants/{$tenantId}/public/documentos"),
        ];

        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }
}

