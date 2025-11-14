<?php

namespace App\Listeners;

use Stancl\Tenancy\Events\DatabaseMigrated;
use Illuminate\Support\Facades\Log;

/**
 * Este listener se ejecuta DESPUÉS de que las migraciones del tenant se han completado
 * Se encarga de:
 * - Crear el usuario administrador
 * - Crear directorios de storage
 * - Crear enlaces simbólicos
 */
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
     */
    public function handle(DatabaseMigrated $event): void
    {
        try {
            $tenant = $event->tenant;
            $tenantId = $tenant->getTenantKey();
            
            Log::info("Configuración post-migración para tenant: {$tenantId}");
            
            // Recuperar los datos del admin desde la caché
            $adminData = \Illuminate\Support\Facades\Cache::get("tenant_admin_data_{$tenantId}");
            
            Log::info("Datos admin recuperados de caché", ['has_data' => !empty($adminData)]);
            
            // Inicializar el contexto del tenant
            tenancy()->initialize($tenant);
            
            // Crear el usuario admin en la base de datos del tenant
            if ($adminData) {
                \App\Models\User::create([
                    'nombre' => $adminData['nombre'],
                    'apellidos' => $adminData['apellidos'],
                    'telefono' => $adminData['telefono'],
                    'email' => $adminData['email'],
                    'password' => $adminData['password'], // Ya viene hasheado
                    'genero' => $adminData['genero'],
                    'edad' => $adminData['edad'],
                    'rol' => 'admin',
                ]);
                Log::info("Usuario admin creado para tenant: {$tenantId}");
                
                // Limpiar la caché
                \Illuminate\Support\Facades\Cache::forget("tenant_admin_data_{$tenantId}");
            } else {
                Log::warning("No se encontraron datos del admin en caché para tenant: {$tenantId}");
            }
            
            // Finalizar el contexto del tenant
            tenancy()->end();

            // Crear directorios de storage
            $this->createTenantStorageDirectories($tenantId);
            Log::info("Directorios de storage creados para el tenant: {$tenantId}");

            // Crear enlace simbólico
            $this->createStorageLink($tenantId);
            Log::info("Enlace simbólico de storage creado para el tenant: {$tenantId}");

            Log::info("Configuración completada exitosamente para el tenant: {$tenantId}");

        } catch (\Exception $e) {
            Log::error("Error al configurar el tenant {$event->tenant->id}: {$e->getMessage()}");
            Log::error("Stack trace: {$e->getTraceAsString()}");
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

