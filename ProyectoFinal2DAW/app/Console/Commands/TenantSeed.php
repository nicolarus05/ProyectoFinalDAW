<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Cita;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class TenantSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:seed 
                            {id : ID del tenant a poblar}
                            {--users=5 : Número de usuarios a crear}
                            {--clientes=10 : Número de clientes a crear}
                            {--servicios=5 : Número de servicios a crear}
                            {--citas=20 : Número de citas a crear}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pobla un tenant con datos de demostración';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->argument('id');
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("❌ Tenant con ID '{$tenantId}' no encontrado");
            return Command::FAILURE;
        }

        $this->info("🌱 Poblando tenant: {$tenant->getName()}");
        $this->info("   ID: {$tenant->id}");
        $domain = $tenant->domains->first()?->domain ?? 'N/A';
        $this->info("   Dominio: {$domain}");
        $this->newLine();

        if (!$this->confirm('¿Desea continuar con la creación de datos de prueba?', true)) {
            $this->info('❌ Operación cancelada');
            return Command::SUCCESS;
        }

        // Inicializar tenant
        tenancy()->initialize($tenant);

        $faker = Faker::create('es_ES');

        // Contadores
        $counts = [
            'users' => 0,
            'clientes' => 0,
            'servicios' => 0,
            'citas' => 0,
        ];

        // Crear usuarios
        $this->info("👥 Creando usuarios...");
        $users = [];
        $numUsers = (int) $this->option('users');
        
        for ($i = 0; $i < $numUsers; $i++) {
            $user = User::create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'role' => $faker->randomElement(['admin', 'empleado', 'usuario']),
            ]);
            $users[] = $user;
            $counts['users']++;
            $this->line("   ✓ {$user->name} ({$user->email})");
        }

        // Crear clientes
        $this->newLine();
        $this->info("🧑‍💼 Creando clientes...");
        $clientes = [];
        $numClientes = (int) $this->option('clientes');
        
        for ($i = 0; $i < $numClientes; $i++) {
            $cliente = Cliente::create([
                'nombre' => $faker->firstName(),
                'apellidos' => $faker->lastName(),
                'email' => $faker->unique()->safeEmail(),
                'telefono' => $faker->numerify('6########'),
                'observaciones' => $faker->optional(0.3)->sentence(),
            ]);
            $clientes[] = $cliente;
            $counts['clientes']++;
            if ($i < 3) { // Mostrar solo los primeros 3
                $this->line("   ✓ {$cliente->nombre} {$cliente->apellidos}");
            }
        }
        if ($numClientes > 3) {
            $this->line("   ... y " . ($numClientes - 3) . " más");
        }

        // Crear servicios
        $this->newLine();
        $this->info("💈 Creando servicios...");
        $servicios = [];
        $numServicios = (int) $this->option('servicios');
        
        $serviciosDemo = [
            ['nombre' => 'Corte de Pelo', 'duracion' => 30, 'precio' => 15.00],
            ['nombre' => 'Corte + Barba', 'duracion' => 45, 'precio' => 20.00],
            ['nombre' => 'Tinte', 'duracion' => 60, 'precio' => 35.00],
            ['nombre' => 'Peinado', 'duracion' => 20, 'precio' => 10.00],
            ['nombre' => 'Tratamiento Capilar', 'duracion' => 40, 'precio' => 25.00],
        ];
        
        for ($i = 0; $i < min($numServicios, count($serviciosDemo)); $i++) {
            $servicio = Servicio::create($serviciosDemo[$i]);
            $servicios[] = $servicio;
            $counts['servicios']++;
            $this->line("   ✓ {$servicio->nombre} - €{$servicio->precio} ({$servicio->duracion}min)");
        }

        // Crear citas
        if (!empty($clientes) && !empty($servicios) && !empty($users)) {
            $this->newLine();
            $this->info("📅 Creando citas...");
            $numCitas = (int) $this->option('citas');
            
            for ($i = 0; $i < $numCitas; $i++) {
                $fechaHora = $faker->dateTimeBetween('-1 month', '+2 months');
                $cliente = $faker->randomElement($clientes);
                $servicio = $faker->randomElement($servicios);
                $user = $faker->randomElement($users);
                
                $cita = Cita::create([
                    'cliente_id' => $cliente->id,
                    'servicio_id' => $servicio->id,
                    'user_id' => $user->id,
                    'fecha_hora' => $fechaHora,
                    'estado' => $faker->randomElement(['pendiente', 'confirmada', 'completada', 'cancelada']),
                    'observaciones' => $faker->optional(0.2)->sentence(),
                ]);
                $counts['citas']++;
                
                if ($i < 3) { // Mostrar solo las primeras 3
                    $this->line("   ✓ {$cliente->nombre} - {$servicio->nombre} - " . $fechaHora->format('Y-m-d H:i'));
                }
            }
            if ($numCitas > 3) {
                $this->line("   ... y " . ($numCitas - 3) . " más");
            }
        }

        // Resumen final
        $this->newLine();
        $this->info("✅ Datos creados exitosamente:");
        $this->table(
            ['Tipo', 'Cantidad'],
            [
                ['Usuarios', $counts['users']],
                ['Clientes', $counts['clientes']],
                ['Servicios', $counts['servicios']],
                ['Citas', $counts['citas']],
            ]
        );

        $this->newLine();
        $this->comment("💡 Acceso de prueba:");
        if (!empty($users)) {
            $firstUser = $users[0];
            $this->line("   Email: {$firstUser->email}");
            $this->line("   Password: password");
        }

        tenancy()->end();

        return Command::SUCCESS;
    }
}
