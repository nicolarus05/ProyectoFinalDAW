<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\User;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create-cli
                            {id : Tenant id/slug}
                            {--name= : Tenant display name}
                            {--email= : Admin email}
                            {--password= : Admin password (plaintext) }
                            {--run-migrations=true : Run tenant migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a tenant, its database, run migrations and create an admin user';

    public function handle()
    {
        $id = Str::lower($this->argument('id'));
        $name = $this->option('name') ?? $id;
        $email = $this->option('email');
        $password = $this->option('password');
        $runMigrations = filter_var($this->option('run-migrations'), FILTER_VALIDATE_BOOLEAN);

        if (Tenant::find($id)) {
            $this->error("Tenant with id '{$id}' already exists.");
            return 1;
        }

        if (!$email || !$password) {
            $this->error('Please provide --email and --password options.');
            return 1;
        }

        $this->info("Creating tenant '{$id}'...");

        try {
            $tenantData = [
                'name' => $name,
                'admin_email' => $email,
            ];

            // Create tenant (Eloquent) to trigger events
            $tenant = Tenant::create([
                'id' => $id,
                'data' => $tenantData,
            ]);

            $this->info('Tenant model created. Creating domain...');

            // Create domain
            $baseDomain = 'localhost';
            $domainName = $id . '.' . $baseDomain;
            $tenant->domains()->create(['domain' => $domainName]);

            $this->info("Domain {$domainName} created.");

            // Create database
            $databaseName = config('tenancy.database.prefix') . $tenant->id . config('tenancy.database.suffix');
            $this->info("Creating database: {$databaseName}");
            DB::connection(config('tenancy.database.central_connection', 'central'))
                ->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");

            $this->info('Database created.');

            if ($runMigrations) {
                $this->info('Initializing tenancy and running migrations...');
                tenancy()->initialize($tenant);

                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant',
                    '--force' => true,
                ]);

                $output = Artisan::output();
                $this->line($output ?: 'Migrations executed (no output).');

                // Create admin user in tenant DB
                $this->info('Creating admin user in tenant database...');
                User::create([
                    'nombre' => 'Admin',
                    'apellidos' => '',
                    'telefono' => '',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'genero' => 'masculino',
                    'edad' => 0,
                    'rol' => 'admin',
                ]);

                tenancy()->end();
                $this->info('Migrations and admin creation finished.');
            }

            $this->info("Tenant '{$id}' created successfully. Domain: http://{$domainName}:90/login");
            return 0;

        } catch (\Exception $e) {
            Log::error('Error creating tenant via command', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
