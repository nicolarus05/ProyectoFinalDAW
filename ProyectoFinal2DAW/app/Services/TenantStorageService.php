<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

/**
 * Servicio para manejo de archivos tenant-aware
 */
class TenantStorageService
{
    /**
     * Subir un archivo al storage del tenant
     *
     * @param UploadedFile $file Archivo a subir
     * @param string $folder Carpeta destino (productos, perfiles, documentos, etc.)
     * @param bool $isPublic Si el archivo debe ser público
     * @param string|null $filename Nombre personalizado (opcional)
     * @return string Path del archivo guardado
     */
    public function store(UploadedFile $file, string $folder, bool $isPublic = true, ?string $filename = null): string
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        
        if ($filename) {
            // Mantener la extensión original
            $extension = $file->getClientOriginalExtension();
            $filename = $filename . '.' . $extension;
            $path = $file->storeAs($folder, $filename, $disk);
        } else {
            // Generar nombre único automáticamente
            $path = $file->store($folder, $disk);
        }

        return $path;
    }

    /**
     * Subir imagen con redimensionamiento (requiere intervention/image)
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param int $maxWidth
     * @param int $maxHeight
     * @return string
     */
    public function storeImage(UploadedFile $file, string $folder, int $maxWidth = 800, int $maxHeight = 800): string
    {
        // Por ahora solo almacenamos sin redimensionar
        // TODO: Implementar redimensionamiento con intervention/image si es necesario
        return $this->store($file, $folder, true);
    }

    /**
     * Eliminar un archivo del storage del tenant
     *
     * @param string $path Path del archivo
     * @param bool $isPublic Si el archivo es público
     * @return bool
     */
    public function delete(string $path, bool $isPublic = true): bool
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    /**
     * Obtener URL pública de un archivo
     *
     * @param string $path Path del archivo
     * @return string
     */
    public function url(string $path): string
    {
        return Storage::disk('tenant_public')->url($path);
    }

    /**
     * Verificar si un archivo existe
     *
     * @param string $path
     * @param bool $isPublic
     * @return bool
     */
    public function exists(string $path, bool $isPublic = true): bool
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Obtener contenido de un archivo
     *
     * @param string $path
     * @param bool $isPublic
     * @return string|null
     */
    public function get(string $path, bool $isPublic = true): ?string
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->get($path);
        }

        return null;
    }

    /**
     * Listar archivos en una carpeta
     *
     * @param string $folder
     * @param bool $isPublic
     * @return array
     */
    public function files(string $folder, bool $isPublic = true): array
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        return Storage::disk($disk)->files($folder);
    }

    /**
     * Eliminar todos los archivos de una carpeta
     *
     * @param string $folder
     * @param bool $isPublic
     * @return bool
     */
    public function deleteDirectory(string $folder, bool $isPublic = true): bool
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        return Storage::disk($disk)->deleteDirectory($folder);
    }

    /**
     * Obtener tamaño de un archivo en bytes
     *
     * @param string $path
     * @param bool $isPublic
     * @return int|false
     */
    public function size(string $path, bool $isPublic = true): int|false
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->size($path);
        }

        return false;
    }

    /**
     * Mover/renombrar un archivo
     *
     * @param string $from
     * @param string $to
     * @param bool $isPublic
     * @return bool
     */
    public function move(string $from, string $to, bool $isPublic = true): bool
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        return Storage::disk($disk)->move($from, $to);
    }

    /**
     * Copiar un archivo
     *
     * @param string $from
     * @param string $to
     * @param bool $isPublic
     * @return bool
     */
    public function copy(string $from, string $to, bool $isPublic = true): bool
    {
        $disk = $isPublic ? 'tenant_public' : 'tenant';
        return Storage::disk($disk)->copy($from, $to);
    }
}
