#!/usr/bin/env php
<?php

/**
 * Script de prueba para verificar el refactoring
 * 
 * Ejecutar: php test_refactoring.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE REFACTORING - PUNTOS 9 Y 10\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$errors = 0;
$warnings = 0;

// Test 1: Verificar que los Traits existen y son válidos
echo "📋 Test 1: Verificando Traits...\n";
$traits = [
    'App\\Traits\\HasFlashMessages',
    'App\\Traits\\HasCrudMessages',
    'App\\Traits\\HasJsonResponses',
];

foreach ($traits as $trait) {
    if (trait_exists($trait)) {
        echo "  ✅ {$trait}\n";
    } else {
        echo "  ❌ {$trait} - NO ENCONTRADO\n";
        $errors++;
    }
}
echo "\n";

// Test 2: Verificar que los Resources existen
echo "📦 Test 2: Verificando API Resources...\n";
$resources = [
    'App\\Http\\Resources\\ClienteResource',
    'App\\Http\\Resources\\EmpleadoResource',
    'App\\Http\\Resources\\ServicioResource',
    'App\\Http\\Resources\\CitaResource',
    'App\\Http\\Resources\\BonoClienteResource',
    'App\\Http\\Resources\\RegistroCobroResource',
];

foreach ($resources as $resource) {
    if (class_exists($resource)) {
        echo "  ✅ {$resource}\n";
    } else {
        echo "  ❌ {$resource} - NO ENCONTRADO\n";
        $errors++;
    }
}
echo "\n";

// Test 3: Verificar que los controladores usan los Traits
echo "🎮 Test 3: Verificando Controladores refactorizados...\n";
$controllers = [
    'App\\Http\\Controllers\\ClienteController',
    'App\\Http\\Controllers\\EmpleadoController',
    'App\\Http\\Controllers\\ServicioController',
    'App\\Http\\Controllers\\CitaController',
    'App\\Http\\Controllers\\HorarioTrabajoController',
    'App\\Http\\Controllers\\RegistroCobroController',
    'App\\Http\\Controllers\\DeudaController',
    'App\\Http\\Controllers\\BonoController',
    'App\\Http\\Controllers\\ProductosController',
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        $uses = class_uses($controller);
        $hasTraits = in_array('App\\Traits\\HasFlashMessages', $uses) &&
                     in_array('App\\Traits\\HasCrudMessages', $uses) &&
                     in_array('App\\Traits\\HasJsonResponses', $uses);
        
        if ($hasTraits) {
            echo "  ✅ " . class_basename($controller) . " - Usa los 3 Traits\n";
        } else {
            echo "  ⚠️  " . class_basename($controller) . " - No usa todos los Traits\n";
            $warnings++;
        }
    } else {
        echo "  ❌ {$controller} - NO ENCONTRADO\n";
        $errors++;
    }
}
echo "\n";

// Test 4: Probar instanciación de Resources
echo "🧪 Test 4: Probando instanciación de Resources...\n";
try {
    // Crear datos de prueba
    $cliente = new \App\Models\Cliente();
    $cliente->id = 999;
    $cliente->direccion = 'Test Address';
    $cliente->fecha_registro = now();
    
    $user = new \App\Models\User();
    $user->nombre = 'Test';
    $user->apellidos = 'User';
    $user->email = 'test@example.com';
    $user->telefono = '123456789';
    
    $cliente->setRelation('user', $user);
    
    $resource = new \App\Http\Resources\ClienteResource($cliente);
    $array = $resource->toArray(request());
    
    if (isset($array['nombre_completo']) && $array['nombre_completo'] === 'Test User') {
        echo "  ✅ ClienteResource - Transformación correcta\n";
        echo "     → nombre_completo: {$array['nombre_completo']}\n";
        echo "     → email: {$array['email']}\n";
    } else {
        echo "  ❌ ClienteResource - Transformación incorrecta\n";
        $errors++;
    }
} catch (\Exception $e) {
    echo "  ❌ Error al probar Resource: {$e->getMessage()}\n";
    $errors++;
}
echo "\n";

// Test 5: Verificar métodos de Traits
echo "🔧 Test 5: Verificando métodos de Traits...\n";
try {
    $reflection = new ReflectionClass('App\\Traits\\HasFlashMessages');
    $methods = $reflection->getMethods();
    $methodCount = count($methods);
    
    if ($methodCount >= 8) {
        echo "  ✅ HasFlashMessages - {$methodCount} métodos\n";
    } else {
        echo "  ⚠️  HasFlashMessages - Solo {$methodCount} métodos (esperados 8)\n";
        $warnings++;
    }
    
    $reflection = new ReflectionClass('App\\Traits\\HasJsonResponses');
    $methods = $reflection->getMethods();
    $methodCount = count($methods);
    
    if ($methodCount >= 8) {
        echo "  ✅ HasJsonResponses - {$methodCount} métodos\n";
    } else {
        echo "  ⚠️  HasJsonResponses - Solo {$methodCount} métodos (esperados 8)\n";
        $warnings++;
    }
    
    $reflection = new ReflectionClass('App\\Traits\\HasCrudMessages');
    $methods = $reflection->getMethods();
    $methodCount = count($methods);
    
    if ($methodCount >= 8) {
        echo "  ✅ HasCrudMessages - {$methodCount} métodos\n";
    } else {
        echo "  ⚠️  HasCrudMessages - Solo {$methodCount} métodos (esperados 8)\n";
        $warnings++;
    }
} catch (\Exception $e) {
    echo "  ❌ Error al verificar métodos: {$e->getMessage()}\n";
    $errors++;
}
echo "\n";

// Test 6: Verificar archivos de documentación
echo "📚 Test 6: Verificando documentación...\n";
$docs = [
    'IMPLEMENTACION_REFACTORING.md',
    'Mejoras.md',
];

foreach ($docs as $doc) {
    if (file_exists(__DIR__ . '/' . $doc)) {
        $size = filesize(__DIR__ . '/' . $doc);
        echo "  ✅ {$doc} ({$size} bytes)\n";
    } else {
        echo "  ❌ {$doc} - NO ENCONTRADO\n";
        $errors++;
    }
}
echo "\n";

// Resumen final
echo "═══════════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE VERIFICACIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($errors === 0 && $warnings === 0) {
    echo "✅ REFACTORING COMPLETADO EXITOSAMENTE\n";
    echo "   - 3 Traits creados y verificados\n";
    echo "   - 6 API Resources creados y funcionales\n";
    echo "   - 9 Controladores refactorizados correctamente\n";
    echo "   - Documentación completa\n\n";
    echo "🎉 ¡Todo funcionando perfectamente!\n\n";
    exit(0);
} else {
    echo "⚠️  VERIFICACIÓN COMPLETADA CON OBSERVACIONES\n";
    echo "   - Errores críticos: {$errors}\n";
    echo "   - Advertencias: {$warnings}\n\n";
    
    if ($errors > 0) {
        echo "❌ Hay errores que deben ser corregidos.\n\n";
        exit(1);
    } else {
        echo "⚠️  Hay advertencias, pero el refactoring es funcional.\n\n";
        exit(0);
    }
}
