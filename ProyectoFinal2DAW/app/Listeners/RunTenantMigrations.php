<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantCreated;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunTenantMigrations
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * 
     * Ejecuta las migraciones y configura el storage automáticamente 
     * cuando se crea un nuevo tenant.
     */
    public function handle(TenantCreated $event): void
    {
        try {
            $tenantId = $event->tenant->id;
            
            // 1. Ejecutar migraciones para el tenant recién creado
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenantId]
            ]);
            Log::info("Migraciones ejecutadas exitosamente para el tenant: {$tenantId}");

            // 2. FASE 6: Crear directorios de storage
            $this->createTenantStorageDirectories($tenantId);
            Log::info("Directorios de storage creados para el tenant: {$tenantId}");

            // 3. FASE 6: Crear enlace simbólico
            $this->createStorageLink($tenantId);
            Log::info("Enlace simbólico de storage creado para el tenant: {$tenantId}");

        } catch (\Exception $e) {
            Log::error("Error al configurar el tenant {$event->tenant->id}: {$e->getMessage()}");
            throw $e; // Re-lanzar para que el controlador pueda manejarlo
        }
    }

    /**
     * Crear directorios de storage para el tenant
     */
    protected function createTenantStorageDirectories(string $tenantId): void
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

    /**
     * Crear enlace simbólico para el storage público del tenant
     */
    protected function createStorageLink(string $tenantId): void
    {
        $target = storage_path("app/tenants/{$tenantId}/public");
        $link = public_path("storage/tenants/{$tenantId}");

        // Crear directorio padre del enlace si no existe
        $linkParent = dirname($link);
        if (!file_exists($linkParent)) {
            mkdir($linkParent, 0755, true);
        }

        // Crear enlace simbólico si no existe
        if (!file_exists($link) && file_exists($target)) {
            symlink($target, $link);
        }
    }
}

