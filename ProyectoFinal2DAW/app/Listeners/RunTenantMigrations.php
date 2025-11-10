<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\TenantSaved;
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
     * DESHABILITADO: Las migraciones se ejecutan manualmente en TenantCreate
     * para evitar conflictos con el ID del tenant durante el guardado.
     */
    public function handle(TenantSaved $event): void
    {
        // Listener deshabilitado - no hacer nada
        return;
        
        try {
            $tenant = $event->tenant;
            
            // Solo ejecutar si el tenant fue recién creado (no en actualizaciones)
            if (!$tenant->wasRecentlyCreated) {
                return;
            }
            
            // Asegurar que el tenant está correctamente guardado y tiene un ID válido
            if (empty($tenant->getTenantKey())) {
                Log::error("Tenant creado sin ID válido. Abortando configuración.");
                throw new \Exception("El tenant no tiene un ID válido");
            }
            
            $tenantId = $tenant->getTenantKey();
            
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
        // No crear enlaces en entorno de testing
        if (app()->environment('testing')) {
            return;
        }

        $target = storage_path("app/tenants/{$tenantId}/public");
        $link = public_path("storage/tenants/{$tenantId}");

        // Crear directorio padre del enlace si no existe
        $linkParent = dirname($link);
        if (!file_exists($linkParent)) {
            @mkdir($linkParent, 0755, true);
        }

        // Crear enlace simbólico si no existe
        if (!file_exists($link) && file_exists($target)) {
            @symlink($target, $link);
        }
    }
}

