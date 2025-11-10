<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * TenantCacheManager
 * 
 * Servicio para gestionar el cache de forma aislada por tenant.
 * Cada tenant tiene su propio namespace de cache.
 */
class TenantCacheManager
{
    /**
     * Obtener el prefijo de cache para el tenant actual
     */
    protected function getCachePrefix(): string
    {
        if (tenancy()->initialized) {
            return 'tenant_' . tenant('id') . '_';
        }
        
        return 'central_';
    }

    /**
     * Obtener el store de cache configurado
     */
    protected function getCacheStore(): string
    {
        return config('cache.default', 'database');
    }

    /**
     * Obtener una clave de cache con el prefijo del tenant
     */
    protected function getKey(string $key): string
    {
        return $this->getCachePrefix() . $key;
    }

    /**
     * Obtener un valor del cache
     */
    public function get(string $key, $default = null)
    {
        $prefixedKey = $this->getKey($key);
        
        Log::debug('TenantCache: GET', [
            'key' => $key,
            'prefixed_key' => $prefixedKey,
            'tenant_id' => tenancy()->initialized ? tenant('id') : 'central'
        ]);
        
        return Cache::store($this->getCacheStore())->get($prefixedKey, $default);
    }

    /**
     * Guardar un valor en el cache
     */
    public function put(string $key, $value, $ttl = null): bool
    {
        $prefixedKey = $this->getKey($key);
        
        Log::debug('TenantCache: PUT', [
            'key' => $key,
            'prefixed_key' => $prefixedKey,
            'ttl' => $ttl,
            'tenant_id' => tenancy()->initialized ? tenant('id') : 'central'
        ]);
        
        if ($ttl === null) {
            return Cache::store($this->getCacheStore())->forever($prefixedKey, $value);
        }
        
        return Cache::store($this->getCacheStore())->put($prefixedKey, $value, $ttl);
    }

    /**
     * Guardar un valor permanentemente
     */
    public function forever(string $key, $value): bool
    {
        return $this->put($key, $value, null);
    }

    /**
     * Verificar si existe una clave en el cache
     */
    public function has(string $key): bool
    {
        $prefixedKey = $this->getKey($key);
        return Cache::store($this->getCacheStore())->has($prefixedKey);
    }

    /**
     * Obtener un valor o ejecutar el callback si no existe
     */
    public function remember(string $key, $ttl, \Closure $callback)
    {
        $prefixedKey = $this->getKey($key);
        
        Log::debug('TenantCache: REMEMBER', [
            'key' => $key,
            'prefixed_key' => $prefixedKey,
            'ttl' => $ttl,
            'tenant_id' => tenancy()->initialized ? tenant('id') : 'central'
        ]);
        
        return Cache::store($this->getCacheStore())->remember($prefixedKey, $ttl, $callback);
    }

    /**
     * Obtener un valor o ejecutar el callback (sin TTL)
     */
    public function rememberForever(string $key, \Closure $callback)
    {
        $prefixedKey = $this->getKey($key);
        
        return Cache::store($this->getCacheStore())->rememberForever($prefixedKey, $callback);
    }

    /**
     * Eliminar un valor del cache
     */
    public function forget(string $key): bool
    {
        $prefixedKey = $this->getKey($key);
        
        Log::debug('TenantCache: FORGET', [
            'key' => $key,
            'prefixed_key' => $prefixedKey,
            'tenant_id' => tenancy()->initialized ? tenant('id') : 'central'
        ]);
        
        return Cache::store($this->getCacheStore())->forget($prefixedKey);
    }

    /**
     * Limpiar todo el cache del tenant actual
     */
    public function flush(): bool
    {
        $prefix = $this->getCachePrefix();
        
        Log::info('TenantCache: FLUSH', [
            'prefix' => $prefix,
            'tenant_id' => tenancy()->initialized ? tenant('id') : 'central'
        ]);
        
        // Para stores que soportan tags (Redis, Memcached)
        if (in_array($this->getCacheStore(), ['redis', 'memcached'])) {
            try {
                return Cache::store($this->getCacheStore())->tags([$prefix])->flush();
            } catch (\Exception $e) {
                Log::warning('TenantCache: No se pueden usar tags, usando flush manual', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Para otros stores, limpiar manualmente por prefijo
        // Nota: esto solo funciona con algunos drivers
        return Cache::store($this->getCacheStore())->flush();
    }

    /**
     * Incrementar un contador
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        $prefixedKey = $this->getKey($key);
        return Cache::store($this->getCacheStore())->increment($prefixedKey, $value);
    }

    /**
     * Decrementar un contador
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        $prefixedKey = $this->getKey($key);
        return Cache::store($this->getCacheStore())->decrement($prefixedKey, $value);
    }

    /**
     * Agregar un valor solo si la clave no existe
     */
    public function add(string $key, $value, $ttl = null): bool
    {
        $prefixedKey = $this->getKey($key);
        
        if ($ttl === null) {
            $ttl = 60; // 1 hora por defecto
        }
        
        return Cache::store($this->getCacheStore())->add($prefixedKey, $value, $ttl);
    }

    /**
     * Obtener múltiples valores del cache
     */
    public function many(array $keys): array
    {
        $prefixedKeys = array_map(fn($key) => $this->getKey($key), $keys);
        $values = Cache::store($this->getCacheStore())->many($prefixedKeys);
        
        // Revertir los prefijos en las claves del resultado
        $result = [];
        foreach ($keys as $index => $originalKey) {
            $prefixedKey = $prefixedKeys[$index];
            $result[$originalKey] = $values[$prefixedKey] ?? null;
        }
        
        return $result;
    }

    /**
     * Guardar múltiples valores en el cache
     */
    public function putMany(array $values, $ttl = null): bool
    {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->getKey($key)] = $value;
        }
        
        if ($ttl === null) {
            $ttl = 60; // 1 hora por defecto
        }
        
        return Cache::store($this->getCacheStore())->putMany($prefixedValues, $ttl);
    }

    /**
     * Obtener todas las claves del tenant actual (solo para algunos stores)
     */
    public function getKeys(): array
    {
        $prefix = $this->getCachePrefix();
        
        // Solo funciona con Redis
        if ($this->getCacheStore() === 'redis') {
            try {
                $redis = Cache::store('redis')->getRedis();
                return $redis->keys($prefix . '*');
            } catch (\Exception $e) {
                Log::error('TenantCache: Error obteniendo claves', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [];
    }
}
