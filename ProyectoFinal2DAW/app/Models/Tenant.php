<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * Modelo personalizado de Tenant que representa un salón de belleza
 * 
 * Cada tenant tiene:
 * - Su propia base de datos (HasDatabase)
 * - Uno o más dominios/subdominios (HasDomains)
 * - Metadatos personalizados (nombre, email, etc.)
 * - Soft deletes (período de gracia de 30 días)
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes;

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'id', // slug del salón (ej: "salonlola")
        'data', // JSON con metadatos
    ];

    /**
     * Cast automático de la columna data a array
     */
    protected $casts = [
        'data' => 'array',
        'backup_created_at' => 'datetime',
    ];

    /**
     * Fechas para soft deletes
     */
    protected $dates = [
        'deleted_at',
        'backup_created_at',
    ];

    /**
     * Obtener el nombre del salón desde los metadatos
     */
    public function getName(): ?string
    {
        return $this->data['nombre'] ?? null;
    }

    /**
     * Obtener el email de contacto del salón
     */
    public function getEmail(): ?string
    {
        return $this->data['email'] ?? null;
    }

    /**
     * Obtener el nombre del administrador del salón
     */
    public function getAdminName(): ?string
    {
        return $this->data['admin_name'] ?? null;
    }

    /**
     * Verificar si el tenant está activo
     */
    public function isActive(): bool
    {
        return ($this->data['active'] ?? true) === true;
    }

    /**
     * Custom database name
     * Genera el nombre de la BD con prefijo "tenant"
     */
    public static function databaseName(string $tenantId): string
    {
        // Sanitizar el ID para nombre de BD (solo alfanuméricos y guiones bajos)
        $sanitized = preg_replace('/[^a-z0-9_]/', '', strtolower($tenantId));
        
        return config('tenancy.database.prefix') . $sanitized . config('tenancy.database.suffix');
    }
}
