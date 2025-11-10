<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantPurge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:purge 
                            {--days=30 : Días desde la eliminación}
                            {--force : No pedir confirmación}
                            {--dry-run : Mostrar qué se eliminaría sin hacerlo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina permanentemente tenants que han sido soft-deleted hace más de X días';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Buscando tenants eliminados hace más de {$days} días...");
        $this->newLine();

        // Buscar tenants eliminados hace más de X días
        $cutoffDate = now()->subDays($days);
        $tenants = Tenant::onlyTrashed()
            ->where('deleted_at', '<=', $cutoffDate)
            ->with('domains')
            ->get();

        if ($tenants->isEmpty()) {
            $this->info("✅ No hay tenants para purgar");
            return Command::SUCCESS;
        }

        // Mostrar tenants a purgar
        $this->warn("⚠️  Se encontraron {$tenants->count()} tenant(s) para purgar:");
        $this->newLine();

        $rows = $tenants->map(function ($tenant) {
            $daysDeleted = $tenant->deleted_at->diffInDays(now());
            return [
                $tenant->id,
                $tenant->data['nombre'] ?? 'N/A',
                $tenant->domains->pluck('domain')->join(', ') ?: 'Sin dominio',
                $tenant->deleted_at->format('Y-m-d H:i'),
                "{$daysDeleted} días",
                $tenant->backup_created_at ? '✅' : '❌',
            ];
        })->toArray();

        $this->table(
            ['ID', 'Nombre', 'Dominio(s)', 'Eliminado', 'Hace', 'Backup'],
            $rows
        );

        // Modo dry-run
        if ($dryRun) {
            $this->newLine();
            $this->info("🔍 MODO DRY-RUN: No se eliminará nada");
            $this->comment("Para purgar realmente, ejecute sin --dry-run");
            return Command::SUCCESS;
        }

        // Confirmación
        if (!$force) {
            $this->newLine();
            $this->error("⚠️  ADVERTENCIA: Esta operación es IRREVERSIBLE");
            $this->line("   • Se eliminarán permanentemente {$tenants->count()} tenant(s)");
            $this->line("   • Se eliminarán sus bases de datos");
            $this->line("   • Se eliminarán sus archivos");
            $this->newLine();

            if (!$this->confirm('¿Desea continuar con la purga permanente?', false)) {
                $this->info('❌ Operación cancelada');
                return Command::SUCCESS;
            }

            // Segunda confirmación
            $this->error("⚠️  ÚLTIMA CONFIRMACIÓN");
            $confirmation = $this->ask("Escriba 'PURGAR PERMANENTEMENTE' para confirmar");
            
            if ($confirmation !== 'PURGAR PERMANENTEMENTE') {
                $this->info("❌ Confirmación incorrecta. Operación cancelada");
                return Command::SUCCESS;
            }
        }

        // Procesar eliminación
        $this->newLine();
        $this->info("🗑️  Iniciando purga permanente...");
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($tenants as $tenant) {
            $this->line("Procesando: {$tenant->getName()} ({$tenant->id})");

            try {
                // Eliminar base de datos
                $dbName = $tenant->tenancy_db_name ?? "tenant_{$tenant->id}";
                DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
                $this->line("   ✓ Base de datos '{$dbName}' eliminada");

                // Eliminar archivos de storage
                $storagePath = "tenants/{$tenant->id}";
                if (Storage::exists($storagePath)) {
                    Storage::deleteDirectory($storagePath);
                    $this->line("   ✓ Archivos eliminados");
                }

                // Eliminar registro del tenant
                $tenant->forceDelete();
                $this->line("   ✓ Registro eliminado permanentemente");

                $success++;
                $this->info("   ✅ Tenant purgado exitosamente");
                $this->newLine();

            } catch (\Exception $e) {
                $failed++;
                $this->error("   ❌ Error: {$e->getMessage()}");
                $this->newLine();
            }
        }

        // Resumen final
        $this->newLine();
        $this->info("📊 Resumen de purga:");
        $this->table(
            ['Estado', 'Cantidad'],
            [
                ['✅ Purgados correctamente', $success],
                ['❌ Fallidos', $failed],
                ['📝 Total procesados', $tenants->count()],
            ]
        );

        if ($success > 0) {
            $this->newLine();
            $this->comment("💡 Recomendaciones:");
            $this->line("   • Verifique los backups en storage/backups/");
            $this->line("   • Considere archivar los backups antiguos");
            $this->line("   • Ejecute: php artisan tenant:list --only-deleted para verificar");
        }

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
