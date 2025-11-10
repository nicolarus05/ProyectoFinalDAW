<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:list 
                            {--deleted : Incluir tenants eliminados}
                            {--only-deleted : Mostrar solo tenants eliminados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista todos los tenants del sistema';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $includeDeleted = $this->option('deleted');
        $onlyDeleted = $this->option('only-deleted');

        // Obtener tenants según filtros
        $query = Tenant::query();
        
        if ($onlyDeleted) {
            $query->onlyTrashed();
            $title = "🗑️  TENANTS ELIMINADOS (Soft Deleted)";
        } elseif ($includeDeleted) {
            $query->withTrashed();
            $title = "📋 TODOS LOS TENANTS (Activos y Eliminados)";
        } else {
            $title = "📋 TENANTS ACTIVOS";
        }

        $tenants = $query->with('domains')->get();

        if ($tenants->isEmpty()) {
            $this->warn("⚠️  No se encontraron tenants");
            return Command::SUCCESS;
        }

        $this->info($title);
        $this->info("Total: {$tenants->count()}");
        $this->newLine();

        // Preparar datos para la tabla
        $rows = $tenants->map(function ($tenant) {
            $status = $tenant->trashed() 
                ? '🗑️ Eliminado' 
                : '✅ Activo';
            
            $deletedAt = $tenant->deleted_at 
                ? $tenant->deleted_at->format('Y-m-d H:i') 
                : '-';
            
            $daysUntilPurge = '';
            if ($tenant->trashed()) {
                $daysLeft = 30 - $tenant->deleted_at->diffInDays(now());
                $daysUntilPurge = $daysLeft > 0 ? "{$daysLeft} días" : "⚠️ Vencido";
            }

            return [
                $tenant->id,
                $tenant->data['nombre'] ?? 'N/A',
                $tenant->domains->pluck('domain')->join(', ') ?: 'Sin dominio',
                $tenant->data['plan'] ?? 'N/A',
                $tenant->created_at->format('Y-m-d'),
                $status,
                $deletedAt,
                $daysUntilPurge,
            ];
        })->toArray();

        // Mostrar tabla
        $headers = ['ID', 'Nombre', 'Dominio(s)', 'Plan', 'Creado', 'Estado', 'Eliminado', 'Purga en'];
        $this->table($headers, $rows);

        // Estadísticas
        $this->newLine();
        $active = $tenants->whereNull('deleted_at')->count();
        $deleted = $tenants->whereNotNull('deleted_at')->count();

        $this->info("📊 Estadísticas:");
        $this->line("   Activos: {$active}");
        if ($deleted > 0) {
            $this->line("   Eliminados: {$deleted}");
        }

        // Comandos útiles
        $this->newLine();
        $this->comment("💡 Comandos útiles:");
        $this->line("   php artisan tenant:list --deleted        - Incluir eliminados");
        $this->line("   php artisan tenant:list --only-deleted   - Solo eliminados");
        $this->line("   php artisan tenant:create <slug> <domain> - Crear nuevo tenant");
        $this->line("   php artisan tenant:delete <id>            - Eliminar tenant");
        $this->line("   php artisan tenant:purge                  - Purgar tenants vencidos");

        return Command::SUCCESS;
    }
}
