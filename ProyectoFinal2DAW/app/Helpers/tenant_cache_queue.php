<?php

use App\Services\TenantCacheManager;

if (!function_exists('tenant_cache')) {
    /**
     * Obtener una instancia del TenantCacheManager
     * 
     * Uso:
     * - tenant_cache()->get('clave')
     * - tenant_cache()->put('clave', 'valor', 3600)
     * - tenant_cache()->remember('clave', 3600, fn() => 'valor')
     * 
     * @return \App\Services\TenantCacheManager
     */
    function tenant_cache(): TenantCacheManager
    {
        return app(TenantCacheManager::class);
    }
}

if (!function_exists('tenant_queue')) {
    /**
     * Obtener el nombre de la queue para el tenant actual
     * 
     * Uso:
     * - dispatch(new MiJob())->onQueue(tenant_queue());
     * - MiJob::dispatch()->onQueue(tenant_queue());
     * 
     * @param string $queueName Nombre base de la queue (default: 'default')
     * @return string
     */
    function tenant_queue(string $queueName = 'default'): string
    {
        if (tenancy()->initialized) {
            return "tenant_" . tenant('id') . "_{$queueName}";
        }
        
        return $queueName;
    }
}

if (!function_exists('dispatch_tenant')) {
    /**
     * Despachar un job asegurando que esté en la queue del tenant actual
     * 
     * Uso:
     * - dispatch_tenant(new MiJob());
     * - dispatch_tenant(new MiJob(), 'emails');
     * 
     * @param mixed $job El job a despachar
     * @param string $queueName Nombre de la queue (default: 'default')
     * @return mixed
     */
    function dispatch_tenant($job, string $queueName = 'default')
    {
        $queue = tenant_queue($queueName);
        
        return dispatch($job)->onQueue($queue);
    }
}
