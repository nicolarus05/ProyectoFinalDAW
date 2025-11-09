<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;

class TenantStorageLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:storage-link {--tenants=* : Specific tenant IDs (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create symbolic links for tenant storage directories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantIds = $this->option('tenants');
        
        if (empty($tenantIds)) {
            // Si no se especifican tenants, obtener todos
            $tenants = Tenant::all();
        } else {
            // Obtener tenants específicos
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
        }

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return 1;
        }

        $this->info("Creating storage links for {$tenants->count()} tenant(s)...");

        foreach ($tenants as $tenant) {
            $this->createStorageLink($tenant->id);
        }

        $this->info('✓ Storage links created successfully!');
        return 0;
    }

    /**
     * Crear enlace simbólico para el storage de un tenant
     */
    protected function createStorageLink(string $tenantId): void
    {
        $target = storage_path("app/tenants/{$tenantId}/public");
        $link = public_path("storage/tenants/{$tenantId}");

        // Crear directorio de destino si no existe
        if (!file_exists($target)) {
            mkdir($target, 0755, true);
            $this->line("  Created directory: {$target}");
        }

        // Crear directorio para el enlace si no existe
        $linkParent = dirname($link);
        if (!file_exists($linkParent)) {
            mkdir($linkParent, 0755, true);
        }

        // Crear enlace simbólico
        if (file_exists($link)) {
            $this->warn("  Link already exists for tenant: {$tenantId}");
            return;
        }

        if (symlink($target, $link)) {
            $this->info("  ✓ Created link for tenant: {$tenantId}");
        } else {
            $this->error("  ✗ Failed to create link for tenant: {$tenantId}");
        }
    }
}

