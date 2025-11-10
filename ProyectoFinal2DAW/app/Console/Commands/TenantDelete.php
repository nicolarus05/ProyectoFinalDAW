<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:delete 
                            {id : ID del tenant a eliminar}
                            {--force : Eliminar permanentemente sin período de gracia}
                            {--skip-backup : Omitir backup automático (no recomendado)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina un tenant (soft delete por defecto, --force para eliminación permanente)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->argument('id');
        $force = $this->option('force');
        $skipBackup = $this->option('skip-backup');

        // Buscar tenant (incluir soft deleted si es --force)
        $tenant = $force 
            ? Tenant::withTrashed()->find($tenantId)
            : Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("❌ Tenant '{$tenantId}' no encontrado");
            return Command::FAILURE;
        }

        // Mostrar información del tenant
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->warn("⚠️  ELIMINAR TENANT");
        $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['ID', $tenant->id],
                ['Nombre', $tenant->data['nombre'] ?? 'N/A'],
                ['Email', $tenant->data['email'] ?? 'N/A'],
                ['Dominio', $tenant->domains->first()?->domain ?? 'N/A'],
                ['Base de Datos', Tenant::databaseName($tenant->id)],
                ['Estado', $tenant->trashed() ? '🗑️ Soft Deleted' : '✅ Activo'],
            ]
        );

        if ($force) {
            $this->error("⚠️  ELIMINACIÓN PERMANENTE - Esta acción NO se puede deshacer");
        } else {
            $this->warn("ℹ️  Soft Delete - El tenant se puede recuperar en 30 días");
        }

        // Primera confirmación
        if (!$this->confirm("¿Estás seguro de eliminar el tenant '{$tenantId}'?", false)) {
            $this->info("❌ Operación cancelada");
            return Command::SUCCESS;
        }

        // Segunda confirmación para force
        if ($force) {
            $this->error("⚠️  ÚLTIMA ADVERTENCIA: Eliminación PERMANENTE");
            $confirmation = $this->ask("Escribe 'ELIMINAR PERMANENTEMENTE' para confirmar");
            
            if ($confirmation !== 'ELIMINAR PERMANENTEMENTE') {
                $this->info("❌ Confirmación incorrecta. Operación cancelada");
                return Command::SUCCESS;
            }
        }

        try {
            // Crear backup automático antes de eliminar (a menos que se omita)
            if (!$skipBackup) {
                $this->info("📦 Creando backup automático...");
                $backupPath = $this->createBackup($tenant);
                
                if ($backupPath) {
                    $this->info("✅ Backup creado: {$backupPath}");
                    
                    // Registrar fecha de backup
                    $tenant->backup_created_at = now();
                    $tenant->saveQuietly(); // No disparar eventos
                } else {
                    $this->warn("⚠️  No se pudo crear backup");
                    if (!$this->confirm("¿Continuar sin backup?", false)) {
                        $this->info("❌ Operación cancelada");
                        return Command::SUCCESS;
                    }
                }
            }

            if ($force) {
                // Eliminación permanente
                $this->warn("🗑️  Eliminando permanentemente...");
                
                // Eliminar base de datos
                $dbName = Tenant::databaseName($tenant->id);
                DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
                $this->info("✅ Base de datos eliminada: {$dbName}");
                
                // Eliminar archivos de storage
                $storagePath = "tenants/{$tenant->id}";
                if (Storage::exists($storagePath)) {
                    Storage::deleteDirectory($storagePath);
                    $this->info("✅ Archivos eliminados: {$storagePath}");
                }
                
                // Eliminar registro (force delete)
                $tenant->forceDelete();
                
                $this->newLine();
                $this->info("✅ Tenant eliminado permanentemente");
                
            } else {
                // Soft delete
                $tenant->delete();
                
                $this->newLine();
                $this->info("✅ Tenant marcado para eliminación");
                $this->info("   Se eliminará permanentemente en 30 días");
                $this->info("   Puedes recuperarlo con: php artisan tenant:restore {$tenantId}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error al eliminar tenant: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Crea un backup del tenant antes de eliminarlo
     */
    private function createBackup(Tenant $tenant): ?string
    {
        try {
            $dbName = Tenant::databaseName($tenant->id);
            $timestamp = now()->format('Y-m-d_His');
            $filename = "deletion_{$tenant->id}_{$timestamp}.sql";
            $backupDir = storage_path('backups');
            $backupPath = "{$backupDir}/{$filename}";

            // Crear directorio si no existe
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Ejecutar mysqldump
            $dbHost = config('database.connections.mysql.host');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s 2>/dev/null',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($backupPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($backupPath)) {
                // Comprimir
                $gzPath = "{$backupPath}.gz";
                exec("gzip {$backupPath}", $output, $returnCode);
                
                return $returnCode === 0 ? $gzPath : $backupPath;
            }

            return null;

        } catch (\Exception $e) {
            $this->warn("Error al crear backup: " . $e->getMessage());
            return null;
        }
    }
}
