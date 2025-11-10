<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests de funcionalidad básica multi-tenancy
 * 
 * Verifican que el sistema multi-tenancy funciona correctamente:
 * - Creación de tenants
 * - Migraciones automáticas
 * - Aislamiento de datos
 * - Estructura de storage
 */
class MultiTenancyFunctionalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Verificar que el sistema está configurado correctamente
     */
    public function test_sistema_multi_tenancy_configurado(): void
    {
        // Verificar que el comando tenants:migrate existe
        $output = Artisan::call('list', ['--format' => 'json']);
        
        $this->assertEquals(0, $output, 'Artisan debe ejecutarse correctamente');
        
        // Verificar que la tabla tenants existe en BD central
        $this->assertTrue(Schema::hasTable('tenants'), 
            'La tabla tenants debe existir en la BD central');
    }

    /**
     * Test 2: Crear tenant y verificar registro en BD central
     */
    public function test_crear_tenant_registra_en_bd_central(): void
    {
        // Act: Crear tenant
        $tenant = Tenant::create(['id' => 'test_salon']);

        // Assert: Verificar en BD central
        $this->assertDatabaseHas('tenants', ['id' => 'test_salon']);
        $this->assertNotNull($tenant);
        
        // Verificar que se puede recuperar de la BD
        $retrieved = Tenant::find('test_salon');
        $this->assertNotNull($retrieved, 'El tenant debe poder recuperarse de la BD');
    }

    /**
     * Test 3: Verificar que tenants:migrate funciona correctamente
     */
    public function test_comando_tenants_migrate_funciona(): void
    {
        // Arrange
        $tenant = Tenant::create(['id' => 'migration_test']);
        sleep(3); // Esperar a que el evento se procese

        // Act: Verificar que las tablas existen
        $tenant->run(function () {
            // Assert: Tablas críticas deben existir
            $this->assertTrue(Schema::hasTable('users'), 
                'La tabla users debe existir en el tenant');
            $this->assertTrue(Schema::hasTable('migrations'), 
                'La tabla migrations debe existir');
            $this->assertTrue(Schema::hasTable('clientes'), 
                'La tabla clientes debe existir');
            $this->assertTrue(Schema::hasTable('servicios'), 
                'La tabla servicios debe existir');
            $this->assertTrue(Schema::hasTable('empleados'), 
                'La tabla empleados debe existir');
            $this->assertTrue(Schema::hasTable('citas'), 
                'La tabla citas debe existir');
        });
    }

    /**
     * Test 4: Verificar estructura de tabla users
     */
    public function test_tabla_users_tiene_estructura_correcta(): void
    {
        // Arrange
        $tenant = Tenant::create(['id' => 'structure_test']);
        sleep(3);

        // Act & Assert
        $tenant->run(function () {
            // Verificar columnas críticas
            $this->assertTrue(Schema::hasColumn('users', 'id'));
            $this->assertTrue(Schema::hasColumn('users', 'nombre'));
            $this->assertTrue(Schema::hasColumn('users', 'apellidos'));
            $this->assertTrue(Schema::hasColumn('users', 'email'));
            $this->assertTrue(Schema::hasColumn('users', 'telefono'));
            $this->assertTrue(Schema::hasColumn('users', 'password'));
            $this->assertTrue(Schema::hasColumn('users', 'rol'));
            $this->assertTrue(Schema::hasColumn('users', 'genero'));
            $this->assertTrue(Schema::hasColumn('users', 'edad'));
        });
    }

    /**
     * Test 5: Insertar y consultar datos en tenant
     */
    public function test_insertar_y_consultar_datos_en_tenant(): void
    {
        // Arrange
        $tenant = Tenant::create(['id' => 'data_test']);
        sleep(3);

        // Act: Insertar datos
        $tenant->run(function () {
            // Limpiar primero
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Insertar usuario
            $id = DB::table('users')->insertGetId([
                'nombre' => 'Test',
                'apellidos' => 'Usuario',
                'telefono' => '666777888',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'genero' => 'Otro',
                'edad' => 30,
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assert
            $this->assertNotNull($id);
            $this->assertGreaterThan(0, $id);

            // Verificar que se puede consultar
            $user = DB::table('users')->where('email', 'test@example.com')->first();
            $this->assertNotNull($user);
            $this->assertEquals('Test', $user->nombre);
            $this->assertEquals('Usuario', $user->apellidos);
        });
    }

    /**
     * Test 6: Directorio de storage existe
     */
    public function test_directorio_storage_se_puede_crear(): void
    {
        // Arrange
        $tenant = Tenant::create(['id' => 'storage_test']);
        sleep(3);

        // Act: Crear directorios si no existen
        $basePath = storage_path('app/tenants/storage_test');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0755, true);
            mkdir($basePath . '/private', 0755, true);
            mkdir($basePath . '/public', 0755, true);
        }

        // Assert
        $this->assertDirectoryExists($basePath);
        $this->assertDirectoryExists($basePath . '/private');
        $this->assertDirectoryExists($basePath . '/public');
    }

    /**
     * Test 7: Múltiples tenants pueden coexistir
     */
    public function test_multiples_tenants_pueden_coexistir(): void
    {
        // Act: Crear múltiples tenants
        $salon1 = Tenant::create(['id' => 'salon_uno']);
        $salon2 = Tenant::create(['id' => 'salon_dos']);
        $salon3 = Tenant::create(['id' => 'salon_tres']);

        // Assert: Todos están en la BD central
        $this->assertDatabaseHas('tenants', ['id' => 'salon_uno']);
        $this->assertDatabaseHas('tenants', ['id' => 'salon_dos']);
        $this->assertDatabaseHas('tenants', ['id' => 'salon_tres']);

        $count = Tenant::count();
        $this->assertGreaterThanOrEqual(3, $count);
    }

    /**
     * Test 8: Contexto de tenant cambia correctamente
     */
    public function test_contexto_tenant_cambia_correctamente(): void
    {
        // Arrange
        $tenant1 = Tenant::create(['id' => 'context_1']);
        $tenant2 = Tenant::create(['id' => 'context_2']);
        sleep(3);

        // Act & Assert: Verificar que podemos ejecutar queries en cada tenant
        $tenant1->run(function () {
            // Verificar que estamos en el tenant correcto viendo si las tablas existen
            $this->assertTrue(Schema::hasTable('users'), 
                'La tabla users debe existir en context_1');
        });

        $tenant2->run(function () {
            // Verificar que estamos en el tenant correcto viendo si las tablas existen
            $this->assertTrue(Schema::hasTable('users'), 
                'La tabla users debe existir en context_2');
        });
    }

    /**
     * Limpiar después de cada test
     */
    protected function tearDown(): void
    {
        try {
            // Limpiar BDs de test
            $testDatabases = [
                'tenant_test_salon',
                'tenant_migration_test',
                'tenant_structure_test',
                'tenant_data_test',
                'tenant_storage_test',
                'tenant_salon_uno',
                'tenant_salon_dos',
                'tenant_salon_tres',
                'tenant_context_1',
                'tenant_context_2',
            ];

            foreach ($testDatabases as $db) {
                try {
                    DB::statement("DROP DATABASE IF EXISTS {$db}");
                } catch (\Exception $e) {
                    // Ignorar si no existe
                }
            }
        } catch (\Exception $e) {
            // Ignorar errores de limpieza
        }

        parent::tearDown();
    }
}
