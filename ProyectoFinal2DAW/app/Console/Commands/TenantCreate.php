<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TenantCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {slug : Identificador único del tenant (alfanumérico, guiones, 3-20 caracteres)}
                            {domain : Dominio del tenant (ej: salon1.misalon.com)}
                            {--name= : Nombre del salón}
                            {--email= : Email de contacto}
                            {--plan=basico : Plan del tenant (basico|profesional|premium)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un nuevo tenant con su base de datos y dominio';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');
        $domain = $this->argument('domain');
        
        // Validar slug
        if (!$this->validateSlug($slug)) {
            $this->error('❌ El slug debe ser alfanumérico, guiones permitidos, entre 3 y 20 caracteres');
            return Command::FAILURE;
        }

        // Verificar si el tenant ya existe
        if (Tenant::find($slug)) {
            $this->error("❌ El tenant '{$slug}' ya existe");
            return Command::FAILURE;
        }

        // Verificar si el dominio ya está en uso
        if (\Stancl\Tenancy\Database\Models\Domain::where('domain', $domain)->exists()) {
            $this->error("❌ El dominio '{$domain}' ya está en uso");
            return Command::FAILURE;
        }

        // Validar longitud del nombre de BD (MySQL limit: 64 caracteres)
        $dbName = Tenant::databaseName($slug);
        if (strlen($dbName) > 64) {
            $this->error("❌ El nombre de la base de datos sería demasiado largo: {$dbName} (" . strlen($dbName) . " caracteres, máximo 64)");
            $this->info("💡 Intenta con un slug más corto");
            return Command::FAILURE;
        }

        $this->info("🚀 Creando tenant '{$slug}'...");
        $this->newLine();

        try {
            // Crear tenant estableciendo explícitamente el id como string y usando save()
            // Esto evita posibles interferencias de traits/boot methods que puedan alterar el id
            $tenant = new Tenant();
            $primaryKey = $tenant->getKeyName();
            $tenant->setAttribute($primaryKey, (string) $slug);
            $tenant->data = [
                'nombre' => $this->option('name') ?? ucfirst($slug),
                'email' => $this->option('email') ?? "{$slug}@example.com",
                'plan' => $this->option('plan'),
                'created_by' => 'artisan',
                'active' => true,
            ];

            $tenant->save();

            // Refrescar modelo para obtener relaciones/eventos
            $tenant->refresh();

            $this->info("✅ Tenant creado: {$tenant->id}");
            $this->info("   BD: {$dbName}");

            // Crear dominio relacionado
            $tenant->domains()->create([
                'domain' => $domain,
            ]);

            $this->info("✅ Dominio asignado: {$domain}");

            // Las migraciones se ejecutan automáticamente por el listener TenantCreated
            $this->newLine();
            $this->info("⏳ Esperando que las migraciones se ejecuten automáticamente...");
            sleep(3); // Dar tiempo al listener

            $this->newLine();
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🎉 Tenant creado exitosamente!");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID/Slug', $tenant->id],
                    ['Nombre', $tenant->data['nombre']],
                    ['Email', $tenant->data['email']],
                    ['Plan', $tenant->data['plan']],
                    ['Dominio', $domain],
                    ['Base de Datos', $dbName],
                    ['URL', "https://{$domain}"],
                ]
            );

            $this->newLine();
            $this->comment("💡 Comandos útiles:");
            $this->line("   php artisan tenant:seed {$slug}  - Poblar con datos de prueba");
            $this->line("   php artisan tenants:list          - Ver todos los tenants");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error al crear tenant: " . $e->getMessage());
            $this->error("   Stack trace: " . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Valida que el slug cumpla con los requisitos
     */
    private function validateSlug(string $slug): bool
    {
        // Solo alfanuméricos y guiones, entre 3 y 20 caracteres
        return preg_match('/^[a-z0-9\-]{3,20}$/', $slug) === 1;
    }
}
