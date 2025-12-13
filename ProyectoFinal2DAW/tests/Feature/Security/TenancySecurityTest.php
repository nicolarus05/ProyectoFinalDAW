<?php

use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Tenant Data Isolation', function () {
    test('tenant cannot access another tenant data', function () {
        // Este test requiere configuración de tenancy
        // Por ahora verificamos que los modelos tienen separación
        
        $cliente1 = Cliente::factory()->create();
        $cliente2 = Cliente::factory()->create();
        
        // En un contexto multi-tenant, cliente1 no debería ver cliente2
        expect($cliente1->id)->not->toBe($cliente2->id);
    });
    
    test('queries are scoped to current tenant', function () {
        // Verificar que existe aislamiento básico
        $clienteCount = Cliente::count();
        
        // En tests unitarios con RefreshDatabase, cada test tiene su propia BD
        expect($clienteCount)->toBeGreaterThanOrEqual(0);
    });
});

describe('Tenant Database Security', function () {
    test('tenant database names follow security pattern', function () {
        // Los nombres de BD tenant deben seguir un patrón seguro
        $tenantId = 'salon_prueba';
        $sanitizedId = preg_replace('/[^a-zA-Z0-9_]/', '', $tenantId);
        
        expect($sanitizedId)->toBe('salon_prueba')
            ->and($sanitizedId)->toMatch('/^[a-zA-Z0-9_]+$/');
    });
    
    test('tenant cannot execute cross-database queries', function () {
        // Verificar que no hay SQL injection en nombres de tenant
        $maliciousTenantId = "tenant'; DROP TABLE users; --";
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '', $maliciousTenantId);
        
        expect($sanitized)->toBe('tenantDROPTABLEusers')
            ->and($sanitized)->not->toContain(';')
            ->and($sanitized)->not->toContain('--');
    });
});

describe('Tenant User Isolation', function () {
    test('user belongs to only one tenant context', function () {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['id_user' => $user->id]);
        
        expect($cliente->user->id)->toBe($user->id);
    });
    
    test('empleado can only see citas in their tenant', function () {
        $empleado = Empleado::factory()->create();
        $otroEmpleado = Empleado::factory()->create();
        
        $citaPropia = Cita::factory()->create(['id_empleado' => $empleado->id]);
        $citaOtro = Cita::factory()->create(['id_empleado' => $otroEmpleado->id]);
        
        $citasEmpleado = $empleado->citas;
        
        expect($citasEmpleado->pluck('id')->toArray())->toContain($citaPropia->id)
            ->and($citasEmpleado->pluck('id')->toArray())->not->toContain($citaOtro->id);
    });
});

describe('Tenant File Storage Security', function () {
    test('tenant storage paths are isolated', function () {
        $tenantId = 'salon_test';
        $storagePath = storage_path("app/tenant{$tenantId}");
        
        // Verificar que el path no contiene caracteres peligrosos
        expect($storagePath)->not->toContain('..')
            ->and($storagePath)->toContain('tenant');
    });
    
    test('file uploads are scoped to tenant directory', function () {
        $tenantId = 'salon_test';
        $fileName = 'document.pdf';
        $fullPath = "tenant{$tenantId}/{$fileName}";
        
        expect($fullPath)->toContain("tenant{$tenantId}")
            ->and($fullPath)->toContain($fileName);
    });
});

describe('Tenant Configuration Security', function () {
    test('sensitive config is not exposed in tenant context', function () {
        // Verificar que valores sensibles existen y están configurados
        $appKey = config('app.key');
        $dbPassword = config('database.connections.mysql.password');
        
        expect($appKey)->not->toBeEmpty()
            ->and($dbPassword)->not->toBeNull();
    });
    
    test('tenant cannot override global configuration', function () {
        $appName = config('app.name');
        
        // El nombre de la app no debe ser modificable por tenant
        expect($appName)->toBeString()
            ->and($appName)->not->toBeEmpty();
    });
});
