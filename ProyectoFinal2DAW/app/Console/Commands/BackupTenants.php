<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Comando para realizar backups de bases de datos de tenants
 */
class BackupTenants extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenants:backup
                            {--tenant=* : IDs de los tenants a respaldar (vacío = todos)}
                            {--central : Incluir backup de la BD central}
                            {--compress : Comprimir con gzip}
                            {--keep=5 : Número de backups a mantener}
                            {--cleanup : Limpiar backups antiguos después del backup}';

    /**
     * The console command description.
     */
    protected $description = 'Realiza backup de las bases de datos de tenants';

    /**
     * Directorio de backups
     */
    protected string $backupDir;

    /**
     * Timestamp del backup
     */
    protected string $timestamp;

    /**
     * Contadores
     */
    protected int $successCount = 0;
    protected int $failedCount = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->timestamp = now()->format('Ymd_His');
        $this->backupDir = storage_path('backups');

        // Crear directorio de backups si no existe
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  📦 BACKUP MULTI-TENANCY - Comando Laravel');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('');

        $this->info("Directorio de backups: {$this->backupDir}");
        $this->info("Timestamp: {$this->timestamp}");
        $this->info('');

        // Verificar si mysqldump está disponible
        if (!$this->checkMysqldumpAvailable()) {
            $this->error('mysqldump no está disponible en el sistema');
            $this->warn('Intente usar el script bash: ./scripts/backup-tenants.sh');
            return Command::FAILURE;
        }

        // Backup de BD central si se solicitó
        if ($this->option('central')) {
            $this->info('═══ Backup de Base de Datos Central ═══');
            $this->backupCentralDatabase();
            $this->info('');
        }

        // Obtener tenants a respaldar
        $tenantIds = $this->option('tenant');
        
        if (empty($tenantIds)) {
            $this->info('═══ Backup de Todos los Tenants ═══');
            $tenants = Tenant::all();
        } else {
            $this->info('═══ Backup de Tenants Específicos ═══');
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
        }

        if ($tenants->isEmpty()) {
            $this->warn('No se encontraron tenants para respaldar');
            return Command::SUCCESS;
        }

        $this->info("Se respaldarán {$tenants->count()} tenant(s)");
        $this->info('');

        // Barra de progreso
        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            try {
                $this->backupTenant($tenant);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->failedCount++;
                Log::error('Error al respaldar tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->info('');
        $this->info('');

        // Limpieza de backups antiguos
        if ($this->option('cleanup')) {
            $this->info('═══ Limpieza de Backups Antiguos ═══');
            $this->cleanupOldBackups();
            $this->info('');
        }

        // Resumen
        $this->showSummary();

        return Command::SUCCESS;
    }

    /**
     * Verificar si mysqldump está disponible
     */
    protected function checkMysqldumpAvailable(): bool
    {
        exec('which mysqldump', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Backup de la base de datos central
     */
    protected function backupCentralDatabase(): void
    {
        $database = config('database.connections.mysql.database');
        $centralDir = $this->backupDir . '/central';

        if (!File::exists($centralDir)) {
            File::makeDirectory($centralDir, 0755, true);
        }

        $filename = "central_{$this->timestamp}.sql";
        $filepath = $centralDir . '/' . $filename;

        $this->info("Respaldando BD central: {$database}");

        try {
            $this->executeMysqldump($database, $filepath);

            // Comprimir si se solicitó
            if ($this->option('compress')) {
                $this->compressFile($filepath);
            }

            $size = $this->formatBytes(File::size($filepath . ($this->option('compress') ? '.gz' : '')));
            $this->info("✓ Backup central completado: {$size}");
        } catch (\Exception $e) {
            $this->error("✗ Error al respaldar BD central: {$e->getMessage()}");
        }
    }

    /**
     * Backup de un tenant específico
     */
    protected function backupTenant(Tenant $tenant): void
    {
        $database = "tenant_{$tenant->id}";
        $tenantDir = $this->backupDir . "/tenant_{$tenant->id}";

        if (!File::exists($tenantDir)) {
            File::makeDirectory($tenantDir, 0755, true);
        }

        $filename = "{$tenant->id}_{$tenant->slug}_{$this->timestamp}.sql";
        $filepath = $tenantDir . '/' . $filename;

        try {
            // Verificar que la BD existe
            if (!$this->databaseExists($database)) {
                throw new \Exception("Base de datos {$database} no existe");
            }

            $this->executeMysqldump($database, $filepath);

            // Comprimir si se solicitó
            if ($this->option('compress')) {
                $this->compressFile($filepath);
                $filepath .= '.gz';
            }

            // Guardar metadata
            $this->saveMetadata($tenant, $filepath);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Ejecutar mysqldump
     */
    protected function executeMysqldump(string $database, string $filepath): void
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $command = sprintf(
            'mysqldump -h%s -P%s -u%s -p%s --single-transaction --routines --triggers --events --add-drop-database --databases %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception(implode("\n", $output));
        }
    }

    /**
     * Comprimir archivo con gzip
     */
    protected function compressFile(string $filepath): void
    {
        exec("gzip -f " . escapeshellarg($filepath), $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Error al comprimir archivo");
        }
    }

    /**
     * Verificar si una base de datos existe
     */
    protected function databaseExists(string $database): bool
    {
        $databases = DB::select('SHOW DATABASES');
        foreach ($databases as $db) {
            if ($db->Database === $database) {
                return true;
            }
        }
        return false;
    }

    /**
     * Guardar metadata del backup
     */
    protected function saveMetadata(Tenant $tenant, string $filepath): void
    {
        $metafile = str_replace('.gz', '', $filepath) . '.meta';

        $metadata = [
            'tenant_id' => $tenant->id,
            'tenant_nombre' => $tenant->nombre,
            'tenant_slug' => $tenant->slug,
            'timestamp' => $this->timestamp,
            'date' => now()->format('Y-m-d H:i:s'),
            'database' => "tenant_{$tenant->id}",
            'original_size' => $this->formatBytes(File::size(str_replace('.gz', '', $filepath))),
        ];

        if ($this->option('compress')) {
            $metadata['compressed_size'] = $this->formatBytes(File::size($filepath));
        }

        $content = '';
        foreach ($metadata as $key => $value) {
            $content .= "{$key}={$value}\n";
        }

        File::put($metafile, $content);
    }

    /**
     * Limpiar backups antiguos
     */
    protected function cleanupOldBackups(): void
    {
        $keep = (int) $this->option('keep');
        $this->info("Manteniendo los {$keep} backups más recientes por tenant");

        $tenantDirs = File::directories($this->backupDir);

        foreach ($tenantDirs as $tenantDir) {
            if (!str_contains($tenantDir, 'tenant_')) {
                continue;
            }

            $backups = collect(File::files($tenantDir))
                ->filter(fn($file) => str_ends_with($file->getFilename(), '.sql.gz') || str_ends_with($file->getFilename(), '.sql'))
                ->sortByDesc(fn($file) => $file->getMTime())
                ->values();

            if ($backups->count() <= $keep) {
                continue;
            }

            $toDelete = $backups->slice($keep);
            
            foreach ($toDelete as $file) {
                File::delete($file->getPathname());
                
                // Eliminar también el .meta
                $metaFile = str_replace(['.sql.gz', '.sql'], '.meta', $file->getPathname());
                if (File::exists($metaFile)) {
                    File::delete($metaFile);
                }
            }

            $tenantId = basename($tenantDir);
            $this->info("✓ {$tenantId}: Eliminados " . $toDelete->count() . " backup(s) antiguos");
        }
    }

    /**
     * Mostrar resumen
     */
    protected function showSummary(): void
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('  📊 RESUMEN DEL BACKUP');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('');
        $this->info("  Tenants exitosos: {$this->successCount}");
        $this->info("  Tenants fallidos: {$this->failedCount}");
        $this->info('');

        $totalSize = $this->getDirectorySize($this->backupDir);
        $this->info("  Espacio total usado: {$this->formatBytes($totalSize)}");
        $this->info('');
        $this->info('═══════════════════════════════════════════════════════════');
    }

    /**
     * Obtener tamaño de un directorio
     */
    protected function getDirectorySize(string $directory): int
    {
        $size = 0;
        foreach (File::allFiles($directory) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    /**
     * Formatear bytes a tamaño legible
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
