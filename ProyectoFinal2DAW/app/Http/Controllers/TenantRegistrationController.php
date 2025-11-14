<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class TenantRegistrationController extends Controller
{
    /**
     * Mostrar formulario de registro de nuevo salón
     */
    public function create()
    {
        return view('tenant.register');
    }

    /**
     * Procesar registro de nuevo salón (tenant)
     */
    public function store(Request $request)
    {
        // DEBUG: Log para verificar que llega aquí
        Log::info('=== INICIO REGISTRO DE TENANT ===');
        Log::info('Datos recibidos:', $request->all());
        
        try {
            // Validar datos del formulario
            $validated = $request->validate([
                'salon_name' => ['required', 'string', 'max:255'],
                'salon_slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:central.tenants,id'],
                'admin_name' => ['required', 'string', 'max:255'],
                'admin_apellidos' => ['required', 'string', 'max:255'],
                'admin_email' => ['required', 'string', 'email', 'max:255'],
                'admin_password' => ['required', 'confirmed', Password::defaults()],
                'admin_telefono' => ['required', 'string', 'max:20'],
                'admin_genero' => ['required', 'in:masculino,femenino,otro'],
                'admin_edad' => ['required', 'integer', 'min:18', 'max:100'],
            ], [
                'salon_slug.unique' => 'Este identificador de salón ya está en uso. Por favor, elige otro.',
                'salon_slug.alpha_dash' => 'El identificador solo puede contener letras, números, guiones y guiones bajos.',
                'admin_password.confirmed' => 'Las contraseñas no coinciden.',
            ]);
            
            Log::info('Validación completada:', $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación:', $e->errors());
            throw $e;
        }

        // NO USAR TRANSACCIONES - Guardar directamente
        try {
            Log::info('Iniciando creación del tenant (SIN transacción)');

            // 1. Crear el Tenant con slug único usando Eloquent (para disparar eventos)
            $tenantId = Str::lower($validated['salon_slug']);
            $tenantData = [
                'name' => $validated['salon_name'],
                'admin_email' => $validated['admin_email'],
                'admin_name' => $validated['admin_name'] . ' ' . $validated['admin_apellidos'],
                // Agregar datos del admin para que el listener los use
                'admin_data' => [
                    'nombre' => $validated['admin_name'],
                    'apellidos' => $validated['admin_apellidos'],
                    'telefono' => $validated['admin_telefono'],
                    'email' => $validated['admin_email'],
                    'password' => Hash::make($validated['admin_password']),
                    'genero' => $validated['admin_genero'],
                    'edad' => $validated['admin_edad'],
                ],
            ];
            
            // Usar Eloquent para que se disparen los eventos (TenantCreated)
            $tenant = Tenant::create([
                'id' => $tenantId,
            ]);
            Log::info('Tenant creado con Eloquent', ['id' => $tenantId]);

            // 2. Crear dominio para el tenant
            $domain = $request->getHost(); // Obtiene el dominio actual
            $baseDomain = $this->getBaseDomain($domain);
            
            // Construir el dominio SIN puerto (el middleware de tenancy ignora puertos)
            $fullDomain = $validated['salon_slug'] . '.' . $baseDomain;
            
            Log::info('Creando dominio', ['base' => $baseDomain, 'slug' => $validated['salon_slug'], 'full_domain' => $fullDomain]);
            
            $tenant->domains()->create([
                'domain' => $fullDomain
            ]);
            Log::info('Dominio creado');

            // 3. Crear la base de datos del tenant
            // El paquete de tenancy crea la base de datos automáticamente
            // a través del evento TenantCreated y el TenantDatabaseManager
            Log::info('La base de datos se creará automáticamente por el sistema de tenancy');

            // 4. Las migraciones se ejecutan automáticamente por el evento TenantCreated
            // Esperamos un momento para que terminen
            sleep(2);
            
            // 5. Crear el usuario administrador directamente en la base de datos del tenant
            // No usamos tenancy()->initialize() para evitar problemas con el cache tagging
            try {
                $tenantDbName = 'tenant' . $tenant->id;
                
                // Cambiar la base de datos temporalmente a la del tenant
                config(['database.connections.mysql.database' => $tenantDbName]);
                DB::purge('mysql');
                DB::reconnect('mysql');
                
                // Insertar el usuario admin
                DB::connection('mysql')->table('users')->insert([
                    'nombre' => $tenantData['admin_data']['nombre'],
                    'apellidos' => $tenantData['admin_data']['apellidos'],
                    'telefono' => $tenantData['admin_data']['telefono'],
                    'email' => $tenantData['admin_data']['email'],
                    'password' => $tenantData['admin_data']['password'],
                    'genero' => $tenantData['admin_data']['genero'],
                    'edad' => $tenantData['admin_data']['edad'],
                    'rol' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Restaurar la conexión a la BD central
                config(['database.connections.mysql.database' => env('DB_DATABASE')]);
                DB::purge('mysql');
                
                Log::info('Usuario admin creado', ['email' => $tenantData['admin_data']['email']]);
                
            } catch (\Exception $e) {
                tenancy()->end();
                Log::error('Error al crear usuario admin: ' . $e->getMessage());
                throw $e;
            }

            Log::info('Guardado completado');

            // 6. Verificar que el tenant se guardó correctamente
            $tenantExists = DB::connection('central')->table('tenants')->where('id', $tenant->id)->exists();
            Log::info('Verificación post-guardado', ['exists' => $tenantExists, 'id' => $tenant->id]);
            
            if (!$tenantExists) {
                throw new \Exception('El tenant no se guardó correctamente en la base de datos');
            }

            // 6. Construir URL del tenant
            $tenantUrl = $request->getScheme() . '://' . $validated['salon_slug'] . '.' . $baseDomain;
            
            // Agregar puerto si es desarrollo
            if ($request->getPort() && !in_array($request->getPort(), [80, 443])) {
                $tenantUrl .= ':' . $request->getPort();
            }
            
            Log::info('Registro completado exitosamente', [
                'tenant_id' => $tenant->id,
                'url' => $tenantUrl . '/login'
            ]);

            // Redirigir directamente al login del tenant con mensaje de éxito
            return redirect($tenantUrl . '/login')
                ->with('success', '¡Salón creado exitosamente! Inicia sesión con tus credenciales.')
                ->with('tenant_name', $validated['salon_name'])
                ->with('admin_email', $validated['admin_email']);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Si algo falla, intentar limpiar el tenant creado
            if (isset($tenant)) {
                try {
                    $tenant->delete();
                } catch (\Exception $deleteException) {
                    // Silenciar error de limpieza
                }
            }

            return back()
                ->withInput($request->except('admin_password', 'admin_password_confirmation'))
                ->withErrors(['error' => 'Error al crear el salón: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtener el dominio base desde el dominio actual
     * Ejemplo: salonlolahernandez.ddns.net:90 -> salonlolahernandez.ddns.net
     *         localhost -> localhost
     */
    private function getBaseDomain(string $domain): string
    {
        // Si es localhost o 127.0.0.1, usar localhost para desarrollo local
        if (in_array($domain, ['localhost', '127.0.0.1'])) {
            return 'localhost';
        }

        // Si el dominio ya es un subdominio (tiene punto), extraer el dominio base
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            // Tomar los últimos 2 o 3 segmentos dependiendo del TLD
            // Para .ddns.net tomar 3, para .com tomar 2
            if (count($parts) >= 3 && in_array(end($parts), ['net', 'com', 'org'])) {
                return implode('.', array_slice($parts, -3));
            }
            return implode('.', array_slice($parts, -2));
        }

        return $domain;
    }

    /**
     * Verificar disponibilidad de slug
     */
    public function checkSlug(Request $request)
    {
        $slug = Str::lower($request->input('slug'));
        $exists = Tenant::where('id', $slug)->exists();
        
        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Este identificador ya está en uso' : 'Identificador disponible'
        ]);
    }
}
