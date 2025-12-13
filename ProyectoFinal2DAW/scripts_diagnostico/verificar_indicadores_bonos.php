#!/usr/bin/env php
<?php

/**
 * SCRIPT DE VERIFICACIÓN - SISTEMA DE INDICADORES DE BONOS
 * ==========================================================
 * Verifica que las 3 fases estén implementadas correctamente:
 * 1. Badges en servicios del formulario
 * 2. Panel informativo del cliente
 * 3. Indicador en lista de citas
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cliente;
use App\Models\BonoCliente;
use App\Models\Cita;
use App\Models\Servicio;
use Carbon\Carbon;

// Seleccionar tenant
$tenant = \App\Models\Tenant::where('id', 'salonlh')->first();
if (!$tenant) {
    echo "❌ Error: Tenant 'salonlh' no encontrado\n";
    exit(1);
}

$tenant->run(function () {
    echo "🔍 VERIFICACIÓN DEL SISTEMA DE INDICADORES DE BONOS\n";
    echo str_repeat("=", 70) . "\n\n";

    $errores = [];
    $advertencias = [];
    $exitos = [];

    // ====================================================================
    // VERIFICACIÓN 1: DATOS DE BONOS DISPONIBLES
    // ====================================================================
    echo "1️⃣ VERIFICACIÓN DE DATOS\n";
    echo str_repeat("-", 70) . "\n";
    
    $clientesConBonos = Cliente::whereHas('bonos', function($query) {
        $query->where('estado', 'activo')
              ->whereHas('servicios', function($servicioQuery) {
                  $servicioQuery->whereRaw('cantidad_usada < cantidad_total');
              });
    })->with(['bonos' => function($query) {
        $query->where('estado', 'activo')
              ->with(['servicios' => function($q) {
                  $q->withPivot('cantidad_total', 'cantidad_usada');
              }, 'plantilla']);
    }])->get();
    
    echo "   👥 Clientes con bonos activos: " . $clientesConBonos->count() . "\n";
    
    if ($clientesConBonos->count() === 0) {
        $advertencias[] = "⚠️  No hay clientes con bonos activos para probar el sistema";
    } else {
        $exitos[] = "✅ Hay clientes con bonos disponibles para visualizar";
        
        echo "\n   📋 Detalles de bonos:\n";
        foreach ($clientesConBonos as $cliente) {
            $nombreCliente = $cliente->user->nombre . ' ' . $cliente->user->apellidos;
            echo "      • $nombreCliente:\n";
            
            foreach ($cliente->bonos as $bono) {
                if ($bono->estado === 'activo') {
                    echo "        - {$bono->plantilla->nombre}:\n";
                    foreach ($bono->servicios as $servicio) {
                        $usado = $servicio->pivot->cantidad_usada;
                        $total = $servicio->pivot->cantidad_total;
                        $restante = $total - $usado;
                        
                        if ($restante > 0) {
                            echo "          ∙ {$servicio->nombre}: {$restante}/{$total} disponibles\n";
                        }
                    }
                }
            }
        }
    }
    
    echo "\n";

    // ====================================================================
    // VERIFICACIÓN 2: CITAS CON BONOS DISPONIBLES
    // ====================================================================
    echo "2️⃣ VERIFICACIÓN DE CITAS CON BONOS\n";
    echo str_repeat("-", 70) . "\n";
    
    $hoy = Carbon::today();
    $citasHoy = Cita::with(['cliente.bonos.servicios', 'servicios'])
        ->whereDate('fecha_hora', $hoy)
        ->where('estado', '!=', 'cancelada')
        ->get();
    
    echo "   📅 Citas de hoy: " . $citasHoy->count() . "\n";
    
    $citasConBono = 0;
    foreach ($citasHoy as $cita) {
        if ($cita->cliente && $cita->cliente->bonos) {
            foreach ($cita->cliente->bonos as $bono) {
                if ($bono->estado === 'activo') {
                    foreach ($cita->servicios as $servicio) {
                        $servicioEnBono = $bono->servicios->firstWhere('id', $servicio->id);
                        if ($servicioEnBono) {
                            $disponible = $servicioEnBono->pivot->cantidad_total - $servicioEnBono->pivot->cantidad_usada;
                            if ($disponible > 0) {
                                $citasConBono++;
                                break 3;
                            }
                        }
                    }
                }
            }
        }
    }
    
    echo "   🎫 Citas donde el cliente tiene bono disponible: $citasConBono\n";
    
    if ($citasConBono > 0) {
        $exitos[] = "✅ El indicador 🎫 se mostrará en $citasConBono cita(s) hoy";
    }
    
    echo "\n";

    // ====================================================================
    // VERIFICACIÓN 3: ARCHIVOS MODIFICADOS
    // ====================================================================
    echo "3️⃣ VERIFICACIÓN DE ARCHIVOS MODIFICADOS\n";
    echo str_repeat("-", 70) . "\n";
    
    // Verificar que el archivo de vista existe y tiene el código correcto
    $vistaCobro = file_get_contents(__DIR__ . '/../resources/views/cobros/create-direct.blade.php');
    
    // Verificar panel de bonos
    if (strpos($vistaCobro, 'panel-bonos-cliente') !== false) {
        $exitos[] = "✅ Panel informativo de bonos implementado en formulario de cobro";
    } else {
        $errores[] = "❌ Panel informativo de bonos NO encontrado en formulario de cobro";
    }
    
    // Verificar badges
    if (strpos($vistaCobro, 'badge-bono-disponible') !== false) {
        $exitos[] = "✅ Badges de bonos implementados en lista de servicios";
    } else {
        $errores[] = "❌ Badges de bonos NO encontrados en lista de servicios";
    }
    
    // Verificar función mostrarPanelBonos
    if (strpos($vistaCobro, 'window.mostrarPanelBonos') !== false) {
        $exitos[] = "✅ Función JavaScript mostrarPanelBonos implementada";
    } else {
        $errores[] = "❌ Función JavaScript mostrarPanelBonos NO encontrada";
    }
    
    // Verificar vista de citas
    $vistaCitas = file_get_contents(__DIR__ . '/../resources/views/citas/index.blade.php');
    if (strpos($vistaCitas, '🎫') !== false && strpos($vistaCitas, '$tieneBono') !== false) {
        $exitos[] = "✅ Indicador de bono implementado en lista de citas";
    } else {
        $errores[] = "❌ Indicador de bono NO encontrado en lista de citas";
    }
    
    // Verificar controlador de citas
    $controladorCitas = file_get_contents(__DIR__ . '/../app/Http/Controllers/CitaController.php');
    if (strpos($controladorCitas, 'cliente.bonos') !== false) {
        $exitos[] = "✅ CitaController carga bonos del cliente";
    } else {
        $errores[] = "❌ CitaController NO carga bonos del cliente";
    }
    
    echo "\n";

    // ====================================================================
    // VERIFICACIÓN 4: CSS Y ESTILOS
    // ====================================================================
    echo "4️⃣ VERIFICACIÓN DE ESTILOS CSS\n";
    echo str_repeat("-", 70) . "\n";
    
    // Verificar estilos de badges
    if (strpos($vistaCobro, 'badge-bono-verde') !== false &&
        strpos($vistaCobro, 'badge-bono-amarillo') !== false &&
        strpos($vistaCobro, 'badge-bono-rojo') !== false) {
        $exitos[] = "✅ Estilos CSS de badges (verde/amarillo/rojo) implementados";
    } else {
        $errores[] = "❌ Estilos CSS de badges NO completos";
    }
    
    // Verificar estilos de cards
    if (strpos($vistaCobro, 'bono-card') !== false) {
        $exitos[] = "✅ Estilos CSS de cards de bonos implementados";
    } else {
        $advertencias[] = "⚠️  Estilos CSS de cards de bonos pueden estar incompletos";
    }
    
    echo "\n";

    // ====================================================================
    // RESUMEN FINAL
    // ====================================================================
    echo str_repeat("=", 70) . "\n";
    echo "📋 RESUMEN DE LA VERIFICACIÓN\n";
    echo str_repeat("=", 70) . "\n\n";
    
    if (count($errores) === 0) {
        echo "✅ ¡SISTEMA DE INDICADORES DE BONOS IMPLEMENTADO CORRECTAMENTE!\n\n";
        
        echo "📊 Funcionalidades verificadas:\n";
        foreach ($exitos as $exito) {
            echo "   $exito\n";
        }
        echo "\n";
        
        echo "🎯 QUÉ VER EN LA INTERFAZ:\n\n";
        echo "   1️⃣ EN EL FORMULARIO DE COBRO:\n";
        echo "      • Al seleccionar un cliente con bonos, aparece un panel morado\n";
        echo "      • El panel muestra todos los bonos activos con servicios disponibles\n";
        echo "      • Al abrir el modal de servicios, aparecen badges:\n";
        echo "        🟢 Verde: 3+ usos disponibles\n";
        echo "        🟡 Amarillo: 1-2 usos disponibles\n";
        echo "        🔴 Rojo: Bono próximo a vencer (< 7 días)\n\n";
        
        echo "   2️⃣ EN LA LISTA DE CITAS:\n";
        echo "      • Las citas de clientes con bonos muestran un icono 🎫\n";
        echo "      • El icono aparece solo si tienen bono para ese servicio\n\n";
        
        if (count($advertencias) > 0) {
            echo "⚠️  ADVERTENCIAS:\n";
            foreach ($advertencias as $advertencia) {
                echo "   $advertencia\n";
            }
            echo "\n";
        }
        
        echo "🚀 SIGUIENTE PASO: Probar en el navegador:\n";
        echo "   1. Ir a: http://salonlh.localhost:90/cobros/create-direct\n";
        echo "   2. Seleccionar un cliente con bonos\n";
        echo "   3. Verificar que aparece el panel y los badges\n";
        echo "   4. Ir al calendario de citas y verificar los iconos 🎫\n\n";
        
    } else {
        echo "❌ SE ENCONTRARON ERRORES EN LA IMPLEMENTACIÓN:\n\n";
        foreach ($errores as $error) {
            echo "   $error\n";
        }
        echo "\n";
        
        if (count($exitos) > 0) {
            echo "✅ Partes implementadas correctamente:\n";
            foreach ($exitos as $exito) {
                echo "   $exito\n";
            }
            echo "\n";
        }
    }
    
    echo "🏁 Verificación completada.\n";
});
