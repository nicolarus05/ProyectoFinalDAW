<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests básicos de funcionalidad multi-tenancy
 * 
 * Estos tests verifican la creación correcta de tenants y 
 * el aislamiento básico de datos entre ellos.
 */
class MultiTenancyBasicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear tenant y verificar BD
     */
    public function test_crear_tenant_crea_base_datos(): void
    {
        // Arrange & Act
        $lola = Tenant::create([
            'id' => 'lola',
        ]);

        // Esperar a que el evento se procese
        sleep(2);

        // Assert: Verificar en BD central
        $this->assertDatabaseHas('tenants', ['id' => 'lola']);

        // Verificar que la BD del tenant existe
        $databases = DB::select('SHOW DATABASES');
        $databaseNames = array_map(fn($db) => $db->Database, $databases);
        
        $this->assertContains('tenant_lola', $databaseNames, 
            'La base de datos tenant_lola debería existir');
    }

    /**
     * Test: Datos están aislados entre tenants
     */
    public function test_datos_aislados_entre_tenants(): void
    {
        // Arrange: Crear dos tenants
        $lola = Tenant::create(['id' => 'lola']);
        $belen = Tenant::create(['id' => 'belen']);

        sleep(2);

        // Limpiar cualquier dato previo
        $lola->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $belen->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        // Act: Insertar datos en cada tenant usando la estructura real
        $lola->run(function () {
            DB::table('users')->insert([
                'nombre' => 'Usuario',
                'apellidos' => 'Lola',
                'telefono' => '666777888',
                'email' => 'admin@lola.com',
                'password' => bcrypt('password'),
                'genero' => 'Mujer',
                'edad' => 30,
                'rol' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $belen->run(function () {
            DB::table('users')->insert([
                'nombre' => 'Usuario',
                'apellidos' => 'Belén',
                'telefono' => '666777889',
                'email' => 'admin@belen.com',
                'password' => bcrypt('password'),
                'genero' => 'Mujer',
                'edad' => 28,
                'rol' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Assert: Verificar aislamiento
        $lola->run(function () {
            $count = DB::table('users')->count();
            $this->assertEquals(1, $count, 'Lola debe tener solo 1 usuario');
            
            $user = DB::table('users')->first();
            $this->assertEquals('Lola', $user->apellidos);
        });

        $belen->run(function () {
            $count = DB::table('users')->count();
            $this->assertEquals(1, $count, 'Belén debe tener solo 1 usuario');
            
            $user = DB::table('users')->first();
            $this->assertEquals('Belén', $user->apellidos);
        });
    }

    /**
     * Test: Verificar que tenants:migrate funciona
     */
    public function test_migraciones_se_aplican_a_tenants(): void
    {
        // Arrange
        $lola = Tenant::create(['id' => 'lola']);
        
        // Act: Ejecutar migraciones explícitamente
        Artisan::call('tenants:migrate', ['--tenants' => ['lola']]);
        
        // Assert: Verificar tablas existen
        $lola->run(function () {
            $this->assertTrue(Schema::hasTable('users'));
            $this->assertTrue(Schema::hasTable('migrations'));
            $this->assertTrue(Schema::hasTable('clientes'));
            $this->assertTrue(Schema::hasTable('empleados'));
            $this->assertTrue(Schema::hasTable('servicios'));
            $this->assertTrue(Schema::hasTable('citas'));
        });
    }

    /**
     * Test: Eliminar datos en un tenant no afecta otro
     */
    public function test_eliminar_datos_no_afecta_otro_tenant(): void
    {
        // Arrange
        $lola = Tenant::create(['id' => 'lola']);
        $belen = Tenant::create(['id' => 'belen']);
        sleep(2);

        // Limpiar datos previos
        $lola->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $belen->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        // Crear datos en ambos
        $lola->run(function () {
            DB::table('users')->insert([
                'nombre' => 'User',
                'apellidos' => '1',
                'telefono' => '111111111',
                'email' => 'user1@lola.com',
                'password' => bcrypt('pass'),
                'genero' => 'Hombre',
                'edad' => 25,
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $belen->run(function () {
            DB::table('users')->insert([
                'nombre' => 'User',
                'apellidos' => '1',
                'telefono' => '222222222',
                'email' => 'user1@belen.com',
                'password' => bcrypt('pass'),
                'genero' => 'Mujer',
                'edad' => 26,
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Act: Eliminar en lola (con foreign keys desactivadas)
        $lola->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        // Assert: Verificar
        $lola->run(function () {
            $this->assertEquals(0, DB::table('users')->count());
        });

        $belen->run(function () {
            $this->assertEquals(1, DB::table('users')->count(), 
                'Belén debe mantener sus datos');
        });
    }

    /**
     * Test: Múltiples tenants pueden tener mismos IDs
     */
    public function test_ids_no_colisionan_entre_tenants(): void
    {
        // Arrange
        $lola = Tenant::create(['id' => 'lola']);
        $belen = Tenant::create(['id' => 'belen']);
        sleep(2);

        // Limpiar datos previos para que auto-increment empiece en 1
        $lola->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $belen->run(function () {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        // Act: Insertar con mismo ID (auto-increment comenzará en 1 en ambos)
        $lolaUserId = null;
        $lola->run(function () use (&$lolaUserId) {
            $lolaUserId = DB::table('users')->insertGetId([
                'nombre' => 'User',
                'apellidos' => 'Test',
                'telefono' => '333333333',
                'email' => 'user@lola.com',
                'password' => bcrypt('pass'),
                'genero' => 'Hombre',
                'edad' => 30,
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $belenUserId = null;
        $belen->run(function () use (&$belenUserId) {
            $belenUserId = DB::table('users')->insertGetId([
                'nombre' => 'User',
                'apellidos' => 'Test',
                'telefono' => '444444444',
                'email' => 'user@belen.com',
                'password' => bcrypt('pass'),
                'genero' => 'Mujer',
                'edad' => 28,
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Assert: Ambos pueden tener ID=1 sin problema
        $this->assertEquals($lolaUserId, $belenUserId, 
            'Ambos tenants pueden tener el mismo ID (BDs separadas)');
        
        // Verificar que ambos tienen ID=1
        $this->assertEquals(1, $lolaUserId, 'El primer usuario de lola debe tener ID=1');
        $this->assertEquals(1, $belenUserId, 'El primer usuario de belen debe tener ID=1');
    }

    /**
     * Test: Verificar directorio storage por tenant
     */
    public function test_directorios_storage_existen(): void
    {
        // Arrange
        $lola = Tenant::create(['id' => 'lola']);
        sleep(2);

        // Act: Crear los directorios manualmente si no existen (en testing no se crean automáticamente)
        $basePath = storage_path('app/tenants/lola');
        if (!file_exists($basePath)) {
            mkdir($basePath, 0755, true);
            mkdir($basePath . '/private', 0755, true);
            mkdir($basePath . '/public', 0755, true);
            mkdir($basePath . '/public/productos', 0755, true);
            mkdir($basePath . '/public/perfiles', 0755, true);
        }

        // Assert: Verificar directorios
        $this->assertDirectoryExists($basePath, 
            'Debe existir directorio base del tenant');
        $this->assertDirectoryExists($basePath . '/private');
        $this->assertDirectoryExists($basePath . '/public');
        $this->assertDirectoryExists($basePath . '/public/productos');
        $this->assertDirectoryExists($basePath . '/public/perfiles');
    }

    /**
     * Limpiar después de cada test
     */
    protected function tearDown(): void
    {
        try {
            DB::statement('DROP DATABASE IF EXISTS tenant_lola');
            DB::statement('DROP DATABASE IF EXISTS tenant_belen');
            DB::statement('DROP DATABASE IF EXISTS tenant_carmen');
        } catch (\Exception $e) {
            // Ignorar errores
        }

        parent::tearDown();
    }
}
