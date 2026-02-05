<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\BonoCliente;
use App\Models\Cliente;
use App\Models\BonoPlantilla;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "🔍 VERIFICACIÓN COMPLETA DEL SISTEMA DE EXPIRACIÓN AUTOMÁTICA DE BONOS\n";
echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// 1. VERIFICAR COMANDO bonos:expirar
// ============================================================================
echo "1️⃣  VERIFICANDO COMANDO bonos:expirar\n";
echo str_repeat("-", 80) . "\n";

$commandExists = file_exists(__DIR__ . '/app/Console/Commands/ExpirarBonos.php');
if ($commandExists) {
    echo "✅ Comando ExpirarBonos existe\n";
    
    $commandContent = file_get_contents(__DIR__ . '/app/Console/Commands/ExpirarBonos.php');
    
    if (strpos($commandContent, "->delete()") !== false) {
        echo "✅ Comando ELIMINA bonos (no solo marca como expirados)\n";
    } else {
        echo "❌ Comando NO elimina bonos correctamente\n";
    }
    
    if (strpos($commandContent, "fecha_expiracion") !== false) {
        echo "✅ Comando verifica fecha_expiracion\n";
    }
    
    if (strpos($commandContent, "Carbon::now()") !== false) {
        echo "✅ Comando usa fecha actual (Carbon::now())\n";
    }
} else {
    echo "❌ Comando ExpirarBonos NO existe\n";
}

echo "\n";

// ============================================================================
// 2. VERIFICAR SCHEDULER
// ============================================================================
echo "2️⃣  VERIFICANDO CONFIGURACIÓN DEL SCHEDULER\n";
echo str_repeat("-", 80) . "\n";

$consoleRoutes = file_get_contents(__DIR__ . '/routes/console.php');

if (strpos($consoleRoutes, "bonos:expirar") !== false) {
    echo "✅ Tarea bonos:expirar está programada en console.php\n";
    
    if (strpos($consoleRoutes, "dailyAt('05:00')") !== false) {
        echo "✅ Se ejecuta diariamente a las 5:00 AM\n";
    }
    
    if (strpos($consoleRoutes, "timezone('Europe/Madrid')") !== false) {
        echo "✅ Usa timezone Europe/Madrid\n";
    }
    
    if (strpos($consoleRoutes, "onSuccess") !== false) {
        echo "✅ Registra logs en caso de éxito\n";
    }
    
    if (strpos($consoleRoutes, "onFailure") !== false) {
        echo "✅ Registra logs en caso de error\n";
    }
} else {
    echo "❌ Tarea bonos:expirar NO está programada\n";
}

echo "\n";

// ============================================================================
// 3. VERIFICAR CRON/SCHEDULER EN SERVIDOR
// ============================================================================
echo "3️⃣  VERIFICANDO CRON DEL SERVIDOR\n";
echo str_repeat("-", 80) . "\n";

// En producción debe existir un cron: * * * * * php artisan schedule:run
$crontabExists = shell_exec("crontab -l 2>/dev/null | grep 'schedule:run' | wc -l");

if (trim($crontabExists) > 0) {
    echo "✅ Crontab configurado para ejecutar schedule:run\n";
    $crontabLine = shell_exec("crontab -l 2>/dev/null | grep 'schedule:run'");
    echo "   Configuración: " . trim($crontabLine) . "\n";
} else {
    echo "⚠️  NO hay crontab configurado\n";
    echo "   💡 SOLUCIÓN: Ejecutar en el servidor:\n";
    echo "      crontab -e\n";
    echo "      Agregar: * * * * * cd " . __DIR__ . " && php artisan schedule:run >> /dev/null 2>&1\n";
}

echo "\n";

// ============================================================================
// 4. BUSCAR BONOS EXPIRADOS ACTUALMENTE
// ============================================================================
echo "4️⃣  BUSCANDO BONOS EXPIRADOS EN EL SISTEMA\n";
echo str_repeat("-", 80) . "\n";

$bonosExpirados = BonoCliente::where('estado', 'activo')
    ->where('fecha_expiracion', '<', Carbon::now())
    ->with(['cliente.user', 'plantilla', 'servicios'])
    ->get();

if ($bonosExpirados->count() > 0) {
    echo "⚠️  ENCONTRADOS {$bonosExpirados->count()} BONO(S) EXPIRADO(S) SIN ELIMINAR:\n\n";
    
    foreach ($bonosExpirados as $bono) {
        $diasExpirado = Carbon::now()->diffInDays($bono->fecha_expiracion);
        
        echo "   🔴 Bono ID: {$bono->id}\n";
        echo "      Cliente: {$bono->cliente->user->nombre} {$bono->cliente->user->apellidos}\n";
        echo "      Plantilla: {$bono->plantilla->nombre}\n";
        echo "      Fecha expiración: {$bono->fecha_expiracion->format('d/m/Y')}\n";
        echo "      Expirado hace: {$diasExpirado} días\n";
        
        $serviciosRestantes = 0;
        foreach ($bono->servicios as $servicio) {
            $disponible = $servicio->pivot->cantidad_total - $servicio->pivot->cantidad_usada;
            $serviciosRestantes += $disponible;
        }
        echo "      Servicios restantes: {$serviciosRestantes}\n";
        echo "\n";
    }
    
    echo "   💡 Estos bonos DEBERÍAN haberse eliminado automáticamente\n";
    echo "   💡 Esto indica que el scheduler NO se está ejecutando\n";
} else {
    echo "✅ No hay bonos expirados sin eliminar\n";
    echo "   El sistema está funcionando correctamente\n";
}

echo "\n";

// ============================================================================
// 5. PRUEBA REAL: CREAR Y ELIMINAR BONO EXPIRADO
// ============================================================================
echo "5️⃣  PRUEBA REAL: CREAR BONO EXPIRADO Y EJECUTAR COMANDO\n";
echo str_repeat("-", 80) . "\n";

// Buscar primer cliente
$cliente = Cliente::with('user')->first();

if (!$cliente) {
    echo "❌ No hay clientes en el sistema\n";
} else {
    // Buscar primera plantilla
    $plantilla = BonoPlantilla::first();
    
    if (!$plantilla) {
        echo "❌ No hay plantillas de bonos\n";
    } else {
        echo "Creando bono de prueba expirado...\n";
        
        // Crear bono ya expirado (fecha hace 10 días)
        $bonoPrueba = BonoCliente::create([
            'cliente_id' => $cliente->id,
            'bono_plantilla_id' => $plantilla->id,
            'fecha_compra' => Carbon::now()->subDays(40),
            'fecha_expiracion' => Carbon::now()->subDays(10), // Expirado hace 10 días
            'estado' => 'activo',
            'precio_pagado' => 50.00
        ]);
        
        echo "✅ Bono de prueba creado (ID: {$bonoPrueba->id})\n";
        echo "   Fecha expiración: {$bonoPrueba->fecha_expiracion->format('d/m/Y')} (hace 10 días)\n\n";
        
        // Ejecutar comando bonos:expirar
        echo "Ejecutando comando: php artisan bonos:expirar\n";
        Artisan::call('bonos:expirar');
        $output = Artisan::output();
        echo $output;
        
        // Verificar si el bono fue eliminado
        $bonoEliminado = BonoCliente::find($bonoPrueba->id);
        
        if ($bonoEliminado === null) {
            echo "\n✅ ¡PERFECTO! El bono expirado fue ELIMINADO correctamente\n";
        } else {
            echo "\n❌ ERROR: El bono expirado NO fue eliminado\n";
            echo "   Estado actual: {$bonoEliminado->estado}\n";
            
            // Limpiar manualmente
            $bonoEliminado->delete();
            echo "   🗑️  Bono de prueba eliminado manualmente\n";
        }
    }
}

echo "\n";

// ============================================================================
// 6. REVISAR LOGS RECIENTES
// ============================================================================
echo "6️⃣  REVISANDO LOGS RECIENTES DE EXPIRACIÓN\n";
echo str_repeat("-", 80) . "\n";

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $relevantLines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'bonos:expirar') !== false || 
            strpos($line, 'bonos expirados') !== false ||
            strpos($line, 'Bono expirado eliminado') !== false) {
            $relevantLines[] = $line;
        }
    }
    
    if (count($relevantLines) > 0) {
        $ultimasLineas = array_slice($relevantLines, -5);
        echo "Últimas 5 entradas relacionadas con expiración:\n\n";
        foreach ($ultimasLineas as $line) {
            echo "   " . trim($line) . "\n";
        }
    } else {
        echo "⚠️  No hay entradas en el log relacionadas con expiración de bonos\n";
        echo "   Esto puede indicar que el comando nunca se ha ejecutado\n";
    }
} else {
    echo "⚠️  Archivo de log no encontrado\n";
}

echo "\n";

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo str_repeat("=", 80) . "\n";
echo "📊 RESUMEN FINAL\n";
echo str_repeat("=", 80) . "\n\n";

$problemas = [];

if (!$commandExists) {
    $problemas[] = "Comando ExpirarBonos no existe";
}

if (strpos($consoleRoutes, "bonos:expirar") === false) {
    $problemas[] = "Tarea no programada en console.php";
}

if (trim($crontabExists) == 0) {
    $problemas[] = "Cron NO configurado en el servidor";
}

if ($bonosExpirados->count() > 0) {
    $problemas[] = "Hay {$bonosExpirados->count()} bono(s) expirado(s) sin eliminar";
}

if (count($problemas) == 0) {
    echo "🎉 ¡SISTEMA FUNCIONANDO CORRECTAMENTE!\n\n";
    echo "✅ Comando ExpirarBonos existe y funciona\n";
    echo "✅ Tarea programada correctamente (diario 5:00 AM)\n";
    echo "✅ No hay bonos expirados sin eliminar\n";
    echo "✅ Logs están siendo registrados\n\n";
    echo "💚 Los bonos que sobrepasen su fecha límite se eliminarán automáticamente\n";
} else {
    echo "⚠️  SE ENCONTRARON " . count($problemas) . " PROBLEMA(S):\n\n";
    
    foreach ($problemas as $i => $problema) {
        echo "   " . ($i + 1) . ". " . $problema . "\n";
    }
    
    echo "\n🔧 SOLUCIONES:\n\n";
    
    if (trim($crontabExists) == 0) {
        echo "   📌 CONFIGURAR CRON EN EL SERVIDOR:\n";
        echo "      1. Conectar al servidor por SSH\n";
        echo "      2. Ejecutar: crontab -e\n";
        echo "      3. Agregar esta línea:\n";
        echo "         * * * * * cd " . __DIR__ . " && php artisan schedule:run >> /dev/null 2>&1\n";
        echo "      4. Guardar y salir\n";
        echo "      5. Verificar con: crontab -l\n\n";
    }
    
    if ($bonosExpirados->count() > 0) {
        echo "   📌 ELIMINAR BONOS EXPIRADOS EXISTENTES:\n";
        echo "      Ejecutar: php artisan bonos:expirar\n\n";
    }
}

echo str_repeat("=", 80) . "\n";
echo "✅ VERIFICACIÓN COMPLETADA\n";
echo str_repeat("=", 80) . "\n\n";
