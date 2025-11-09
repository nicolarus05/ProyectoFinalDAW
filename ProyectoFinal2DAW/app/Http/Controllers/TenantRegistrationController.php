<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
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
        // Validar datos del formulario
        $validated = $request->validate([
            'salon_name' => ['required', 'string', 'max:255'],
            'salon_slug' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:tenants,id'],
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

        try {
            DB::beginTransaction();

            // 1. Crear el Tenant con slug único
            $tenant = Tenant::create([
                'id' => Str::lower($validated['salon_slug']),
                'data' => [
                    'name' => $validated['salon_name'],
                    'admin_email' => $validated['admin_email'],
                    'admin_name' => $validated['admin_name'] . ' ' . $validated['admin_apellidos'],
                    'created_at' => now()->toDateTimeString(),
                ]
            ]);

            // 2. Crear dominio para el tenant
            $domain = $request->getHost(); // Obtiene el dominio actual
            $baseDomain = $this->getBaseDomain($domain);
            
            $tenant->domains()->create([
                'domain' => $validated['salon_slug'] . '.' . $baseDomain
            ]);

            // 3. Las migraciones se ejecutan automáticamente por el listener RunTenantMigrations
            // que escucha el evento TenantCreated

            // 4. Inicializar el contexto del tenant para crear el usuario admin
            tenancy()->initialize($tenant);

            // 5. Crear usuario administrador en la BD del tenant
            User::create([
                'nombre' => $validated['admin_name'],
                'apellidos' => $validated['admin_apellidos'],
                'telefono' => $validated['admin_telefono'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'genero' => $validated['admin_genero'],
                'edad' => $validated['admin_edad'],
                'rol' => 'admin',
            ]);

            // 6. Finalizar contexto de tenant
            tenancy()->end();

            DB::commit();

            // 7. Redirigir al subdominio del nuevo salón con mensaje de éxito
            $tenantUrl = $request->getScheme() . '://' . $validated['salon_slug'] . '.' . $baseDomain;
            
            // Agregar puerto si es desarrollo
            if ($request->getPort() && !in_array($request->getPort(), [80, 443])) {
                $tenantUrl .= ':' . $request->getPort();
            }

            return redirect($tenantUrl . '/login')
                ->with('success', '¡Salón creado exitosamente! Ya puedes iniciar sesión con tus credenciales.');

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
     */
    private function getBaseDomain(string $domain): string
    {
        // Si es localhost o 127.0.0.1, usar un dominio específico para desarrollo
        if (in_array($domain, ['localhost', '127.0.0.1'])) {
            return 'salonlolahernandez.ddns.net';
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
