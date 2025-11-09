<?php

use App\Services\TenantStorageService;

if (!function_exists('tenant_storage')) {
    /**
     * Helper para acceder al servicio de storage del tenant
     *
     * @return TenantStorageService
     */
    function tenant_storage(): TenantStorageService
    {
        return app(TenantStorageService::class);
    }
}

if (!function_exists('tenant_asset')) {
    /**
     * Obtener URL de un archivo en el storage público del tenant
     *
     * @param string $path
     * @return string
     */
    function tenant_asset(string $path): string
    {
        return tenant_storage()->url($path);
    }
}

if (!function_exists('tenant_upload')) {
    /**
     * Subir un archivo al storage del tenant
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @param bool $isPublic
     * @return string
     */
    function tenant_upload($file, string $folder, bool $isPublic = true): string
    {
        return tenant_storage()->store($file, $folder, $isPublic);
    }
}
