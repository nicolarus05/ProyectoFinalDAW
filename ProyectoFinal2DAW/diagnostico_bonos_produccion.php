<?php

/**
 * Script de diagnóstico para el problema de bonos en producción
 * 
 * Este script verifica por qué no se descuentan los usos de bonos en producción
 */

require __DIR__ . '/vendor/autoload.php';

use App\Models\BonoCliente;
use App\Models\Cliente;
use App\Models\RegistroCobro;
use App\Models\BonoUsoDetalle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔍 DIAGNÓSTICO: Sistema de Bonos en Producción\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Verificar conexión a base de datos
echo "1️⃣  VERIFICANDO CONEXIÓN A BASE DE DATOS...\n";
echo str_repeat("-", 80) . "\n";

try {
    $connection = DB::connection();
    $databaseName = $connection->getDatabaseName();
    $driver = $connection->getDriverName();
    
    echo "✅ Conexión establecida:\n";
    echo "   - Driver: {$driver}\n";
    echo "   - Database: {$databaseName}\n";
    echo "   - Host: " . config('database.connections.' . config('database.default') . '.host') . "\n";
    
    // Test de escritura/lectura
    $testTable = DB::select("SHOW TABLES LIKE 'bono_cliente_servicios'");
    if (count($testTable) > 0) {
        echo "✅ Tabla bono_cliente_servicios existe\n";
    } else {
        echo "❌ Tabla bono_cliente_servicios NO existe\n";
    }
} catch (\Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 2. Verificar bonos activos con servicios disponibles
echo "2️⃣  VERIFICANDO BONOS ACTIVOS...\n";
echo str_repeat("-", 80) . "\n";

$bonosActivos = BonoCliente::with(['cliente.user', 'servicios', 'plantilla'])
    ->where('estado', 'activo')
    ->where('fecha_expiracion', '>=', Carbon::now())
    ->get();

echo "Total de bonos activos: " . $bonosActivos->count() . "\n\n";

if ($bonosActivos->count() > 0) {
    foreach ($bonosActivos as $bono) {
        echo "📋 Bono ID: {$bono->id}\n";
        echo "   Cliente: {$bono->cliente->user->nombre} {$bono->cliente->user->apellidos}\n";
        echo "   Plantilla: {$bono->plantilla->nombre}\n";
        echo "   Estado: {$bono->estado}\n";
        echo "   Servicios:\n";
        
        foreach ($bono->servicios as $servicio) {
            $usado = $servicio->pivot->cantidad_usada;
            $total = $servicio->pivot->cantidad_total;
            $disponible = $total - $usado;
            
            $estado = $disponible > 0 ? "✅" : "❌";
            echo "      {$estado} {$servicio->nombre}: {$usado}/{$total} (disponibles: {$disponible})\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️  No hay bonos activos en este momento.\n\n";
}

// 3. Verificar últimos registros de cobro
echo "3️⃣  VERIFICANDO ÚLTIMOS COBROS...\n";
echo str_repeat("-", 80) . "\n";

$ultimosCobros = RegistroCobro::with(['cliente.user', 'servicios'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "Últimos 10 cobros:\n\n";

foreach ($ultimosCobros as $cobro) {
    echo "💰 Cobro #{$cobro->id}\n";
    echo "   Fecha: {$cobro->created_at->format('d/m/Y H:i:s')}\n";
    
    if ($cobro->cliente) {
        echo "   Cliente: {$cobro->cliente->user->nombre} {$cobro->cliente->user->apellidos}\n";
        
        // Verificar si el cliente tiene bonos
        $bonosCliente = BonoCliente::where('cliente_id', $cobro->cliente->id)
            ->where('estado', 'activo')
            ->count();
        echo "   Bonos activos del cliente: {$bonosCliente}\n";
    }
    
    echo "   Total: €" . number_format($cobro->coste, 2) . "\n";
    echo "   Servicios:\n";
    
    foreach ($cobro->servicios as $servicio) {
        $precio = $servicio->pivot->precio ?? $servicio->precio;
        $pagadoConBono = $precio == 0 ? "🎫 CON BONO" : "💵 €" . number_format($precio, 2);
        echo "      - {$servicio->nombre} ({$pagadoConBono})\n";
    }
    echo "\n";
}

// 4. Verificar registro de uso de bonos
echo "4️⃣  VERIFICANDO REGISTRO DE USO DE BONOS...\n";
echo str_repeat("-", 80) . "\n";

$usosRecientes = BonoUsoDetalle::with(['bonoCliente.plantilla', 'servicio', 'cita'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($usosRecientes->count() > 0) {
    echo "Últimos 10 usos de bonos:\n\n";
    
    foreach ($usosRecientes as $uso) {
        echo "📝 Uso ID: {$uso->id}\n";
        echo "   Fecha: {$uso->created_at->format('d/m/Y H:i:s')}\n";
        echo "   Bono: {$uso->bonoCliente->plantilla->nombre} (ID: {$uso->bono_cliente_id})\n";
        echo "   Servicio: {$uso->servicio->nombre}\n";
        echo "   Cantidad usada: {$uso->cantidad_usada}\n";
        
        if ($uso->cita) {
            echo "   Cita ID: {$uso->cita_id}\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️  No hay registros de uso de bonos.\n";
    echo "   Esto indica que los bonos NO se están aplicando correctamente.\n\n";
}

// 5. Test de actualización de pivot
echo "5️⃣  TEST DE ACTUALIZACIÓN DE PIVOT...\n";
echo str_repeat("-", 80) . "\n";

$bonoTest = BonoCliente::with('servicios')
    ->where('estado', 'activo')
    ->where('fecha_expiracion', '>=', Carbon::now())
    ->first();

if ($bonoTest && $bonoTest->servicios->count() > 0) {
    $servicioTest = $bonoTest->servicios->first();
    $cantidadUsadaAntes = $servicioTest->pivot->cantidad_usada;
    
    echo "Test con Bono ID: {$bonoTest->id}\n";
    echo "Servicio: {$servicioTest->nombre}\n";
    echo "Cantidad usada ANTES: {$cantidadUsadaAntes}\n";
    
    try {
        DB::beginTransaction();
        
        // Intentar actualizar
        $nuevaCantidad = $cantidadUsadaAntes + 1;
        $bonoTest->servicios()->updateExistingPivot($servicioTest->id, [
            'cantidad_usada' => $nuevaCantidad
        ]);
        
        // Verificar si se actualizó
        $bonoTest->refresh();
        $bonoTest->load('servicios');
        $servicioActualizado = $bonoTest->servicios->where('id', $servicioTest->id)->first();
        $cantidadUsadaDespues = $servicioActualizado->pivot->cantidad_usada;
        
        echo "Cantidad usada DESPUÉS: {$cantidadUsadaDespues}\n";
        
        if ($cantidadUsadaDespues == $nuevaCantidad) {
            echo "✅ ÉXITO: La actualización funcionó correctamente\n";
            echo "   La tabla pivot se puede actualizar sin problemas.\n";
        } else {
            echo "❌ ERROR: La actualización NO funcionó\n";
            echo "   Esperado: {$nuevaCantidad}, Obtenido: {$cantidadUsadaDespues}\n";
        }
        
        // Hacer rollback para no afectar los datos
        DB::rollBack();
        echo "   (Cambios revertidos - test sin efectos permanentes)\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ ERROR en test de actualización: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No hay bonos activos para hacer el test.\n";
}

echo "\n";

// 6. Verificar configuración de transacciones
echo "6️⃣  VERIFICANDO CONFIGURACIÓN DE TRANSACCIONES...\n";
echo str_repeat("-", 80) . "\n";

try {
    $engineInfo = DB::select("SHOW TABLE STATUS LIKE 'bono_cliente_servicios'");
    if (count($engineInfo) > 0) {
        $engine = $engineInfo[0]->Engine;
        echo "Motor de la tabla: {$engine}\n";
        
        if (strtolower($engine) === 'innodb') {
            echo "✅ InnoDB soporta transacciones correctamente\n";
        } else {
            echo "⚠️  Motor {$engine} puede no soportar transacciones completamente\n";
        }
    }
    
    // Verificar nivel de aislamiento
    $isolation = DB::select("SELECT @@transaction_isolation")[0]->{'@@transaction_isolation'};
    echo "Nivel de aislamiento: {$isolation}\n";
    
} catch (\Exception $e) {
    echo "❌ Error al verificar configuración: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Verificar permisos de escritura
echo "7️⃣  VERIFICANDO PERMISOS DE ESCRITURA...\n";
echo str_repeat("-", 80) . "\n";

try {
    $user = DB::select("SELECT USER()")[0]->{'USER()'};
    echo "Usuario conectado: {$user}\n";
    
    // Verificar permisos
    $grants = DB::select("SHOW GRANTS");
    $tieneUpdate = false;
    
    foreach ($grants as $grant) {
        $grantText = array_values((array)$grant)[0];
        if (stripos($grantText, 'UPDATE') !== false || stripos($grantText, 'ALL PRIVILEGES') !== false) {
            $tieneUpdate = true;
            break;
        }
    }
    
    if ($tieneUpdate) {
        echo "✅ Usuario tiene permisos de UPDATE\n";
    } else {
        echo "❌ Usuario NO tiene permisos de UPDATE\n";
        echo "   Esto podría causar que las actualizaciones fallen silenciosamente.\n";
    }
    
} catch (\Exception $e) {
    echo "⚠️  No se pudieron verificar permisos: " . $e->getMessage() . "\n";
}

echo "\n";

// 8. Verificar caché/Redis
echo "8️⃣  VERIFICANDO CACHÉ...\n";
echo str_repeat("-", 80) . "\n";

$cacheDriver = config('cache.default');
echo "Driver de caché configurado: {$cacheDriver}\n";

if ($cacheDriver === 'redis') {
    try {
        $redis = \Illuminate\Support\Facades\Redis::connection();
        $redis->ping();
        echo "✅ Conexión a Redis activa\n";
        echo "   IMPORTANTE: Si hay caché agresivo, podría mostrar datos antiguos.\n";
    } catch (\Exception $e) {
        echo "❌ Error conectando a Redis: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 9. Resumen y recomendaciones
echo "9️⃣  RESUMEN Y RECOMENDACIONES\n";
echo str_repeat("=", 80) . "\n\n";

echo "📊 ANÁLISIS:\n";

if ($usosRecientes->count() == 0) {
    echo "   🔴 PROBLEMA DETECTADO: No hay registros de uso de bonos\n";
    echo "      Esto indica que el código de descuento NO se está ejecutando.\n\n";
    
    echo "   ✅ POSIBLES CAUSAS:\n";
    echo "      1. La condición if (!seVendeBono && clienteId) está bloqueando la lógica\n";
    echo "      2. Los bonos no se están encontrando (WHERE fecha_expiracion >= NOW())\n";
    echo "      3. La transacción se está revirtiendo (DB::rollBack())\n";
    echo "      4. El código no se está desplegando correctamente en producción\n";
    echo "      5. Problema con caché de código (OpCache, APCu)\n\n";
} else {
    echo "   ✅ Los bonos SÍ se están usando (hay {$usosRecientes->count()} usos recientes)\n\n";
}

echo "   🔧 RECOMENDACIONES:\n";
echo "      1. Añadir logging en RegistroCobroController línea 520-640\n";
echo "      2. Verificar que el código en producción sea el más reciente\n";
echo "      3. Limpiar caché de código: php artisan optimize:clear\n";
echo "      4. Revisar logs de Laravel: storage/logs/laravel.log\n";
echo "      5. Activar query logging para ver qué SQL se ejecuta\n\n";

echo str_repeat("=", 80) . "\n";
echo "✅ DIAGNÓSTICO COMPLETADO\n";
echo str_repeat("=", 80) . "\n\n";

// 10. Generar comando de test
echo "Para hacer un test real, ejecuta en producción:\n";
echo "   1. Identifica un bono activo (ID: " . ($bonoTest ? $bonoTest->id : "N/A") . ")\n";
echo "   2. Haz un cobro con un servicio que tenga ese bono\n";
echo "   3. Verifica si se descuenta en la base de datos directamente:\n";
echo "      SELECT * FROM bono_cliente_servicios WHERE bono_cliente_id = " . ($bonoTest ? $bonoTest->id : "N/A") . ";\n";
echo "   4. Verifica si se creó un registro de uso:\n";
echo "      SELECT * FROM bono_uso_detalle ORDER BY created_at DESC LIMIT 5;\n\n";
