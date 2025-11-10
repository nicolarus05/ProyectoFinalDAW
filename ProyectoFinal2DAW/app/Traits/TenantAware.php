<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait TenantAware
 * 
 * Este trait permite que los Jobs mantengan el contexto del tenant
 * cuando son encolados y ejecutados posteriormente.
 * 
 * Uso:
 * 1. Añade el trait a tu Job class
 * 2. El trait automáticamente guarda el tenant_id al encolar
 * 3. Restaura el contexto antes de ejecutar el job
 */
trait TenantAware
{
    /**
     * El ID del tenant para este job
     */
    public ?string $tenantId = null;

    /**
     * Inicializar el tenant al crear el job
     */
    public function __construct()
    {
        // Capturar el tenant actual cuando se crea el job
        if (tenancy()->initialized) {
            $this->tenantId = tenant('id');
            
            Log::debug('TenantAware: Job creado con tenant', [
                'job' => get_class($this),
                'tenant_id' => $this->tenantId
            ]);
        }
    }

    /**
     * Inicializar el contexto del tenant antes de ejecutar el job
     */
    public function initializeTenantContext(): void
    {
        if ($this->tenantId) {
            // Si ya estamos en el tenant correcto, no hacer nada
            if (tenancy()->initialized && tenant('id') === $this->tenantId) {
                Log::debug('TenantAware: Ya en contexto correcto', [
                    'tenant_id' => $this->tenantId
                ]);
                return;
            }

            try {
                // Buscar el tenant
                $tenant = \App\Models\Tenant::find($this->tenantId);
                
                if ($tenant) {
                    // Inicializar el contexto del tenant
                    tenancy()->initialize($tenant);
                    
                    Log::info('TenantAware: Contexto de tenant inicializado', [
                        'job' => get_class($this),
                        'tenant_id' => $this->tenantId,
                        'tenant_nombre' => $tenant->nombre ?? 'N/A'
                    ]);
                } else {
                    Log::error('TenantAware: Tenant no encontrado', [
                        'job' => get_class($this),
                        'tenant_id' => $this->tenantId
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('TenantAware: Error al inicializar contexto', [
                    'job' => get_class($this),
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage()
                ]);
                
                throw $e;
            }
        } else {
            Log::warning('TenantAware: Job sin tenant_id', [
                'job' => get_class($this)
            ]);
        }
    }

    /**
     * Obtener las propiedades que deben serializarse
     */
    public function __sleep()
    {
        $properties = [];
        
        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
            if (!$property->isStatic()) {
                $properties[] = $property->getName();
            }
        }
        
        return $properties;
    }

    /**
     * Restaurar el contexto al deserializar
     */
    public function __wakeup()
    {
        // Este método se llama al deserializar el job
        // El contexto del tenant se inicializará en el método handle
    }
}
