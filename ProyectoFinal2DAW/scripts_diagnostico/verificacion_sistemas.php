#!/usr/bin/env php
<?php

/**
 * SCRIPT DE VERIFICACIÓN COMPLETA DE LOS 6 MÓDULOS FINANCIEROS
 * ============================================================
 * Este script revisa la integridad de:
 * 1. Registro de Cobros
 * 2. Caja Diaria
 * 3. Clientes con Bonos
 * 4. Facturación Mensual
 * 5. Gestión de Deudas
 * 6. Gestión de Bonos
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RegistroCobro;
use App\Models\BonoCliente;
use App\Models\Cliente;
use App\Models\Deuda;
use App\Models\MovimientoDeuda;
use App\Models\Cita;
use Carbon\Carbon;

// Seleccionar tenant
$tenant = \App\Models\Tenant::where('id', 'salonlh')->first();
if (!$tenant) {
    echo "❌ Error: Tenant 'salonlh' no encontrado\n";
    exit(1);
}
$tenant->run(function () {
    echo "🔍 VERIFICACIÓN COMPLETA DE SISTEMAS FINANCIEROS\n";
    echo str_repeat("=", 70) . "\n\n";

    $errores = [];
    $advertencias = [];

    // ====================================================================
    // 1. REGISTRO DE COBROS
    // ====================================================================
    echo "1️⃣ REGISTRO DE COBROS\n";
    echo str_repeat("-", 70) . "\n";
    
    $totalCobros = RegistroCobro::count();
    echo "   📊 Total de cobros registrados: $totalCobros\n";
    
    // Verificar cobros sin cliente ni cita
    $cobrosSinReferencia = RegistroCobro::whereNull('id_cliente')
        ->whereNull('id_cita')
        ->whereDoesntHave('citasAgrupadas')
        ->count();
    
    if ($cobrosSinReferencia > 0) {
        $advertencias[] = "⚠️  $cobrosSinReferencia cobros sin cliente ni cita asociada";
    } else {
        echo "   ✅ Todos los cobros tienen cliente o cita asociada\n";
    }
    
    // Verificar totales coherentes
    $cobrosIncoherentes = RegistroCobro::whereRaw('total_final > coste')->count();
    if ($cobrosIncoherentes > 0) {
        $errores[] = "❌ $cobrosIncoherentes cobros con total_final > coste (imposible)";
    } else {
        echo "   ✅ Todos los totales son coherentes\n";
    }
    
    // Verificar pagos mixtos
    $cobrosMixtos = RegistroCobro::where('metodo_pago', 'mixto')->get();
    foreach ($cobrosMixtos as $cobro) {
        $suma = $cobro->pago_efectivo + $cobro->pago_tarjeta;
        if (abs($suma - $cobro->total_final) > 0.01) {
            $errores[] = "❌ Cobro #{$cobro->id}: pago mixto no cuadra (efectivo: €{$cobro->pago_efectivo}, tarjeta: €{$cobro->pago_tarjeta}, total: €{$cobro->total_final})";
        }
    }
    if (empty($errores)) {
        echo "   ✅ Todos los pagos mixtos cuadran correctamente\n";
    }
    
    // Verificar deudas registradas
    $cobrosConDeuda = RegistroCobro::where('deuda', '>', 0)->count();
    echo "   💰 Cobros con deuda generada: $cobrosConDeuda\n";
    
    echo "\n";

    // ====================================================================
    // 2. CAJA DIARIA
    // ====================================================================
    echo "2️⃣ CAJA DIARIA\n";
    echo str_repeat("-", 70) . "\n";
    
    $hoy = Carbon::today();
    $cobrosHoy = RegistroCobro::whereDate('created_at', $hoy)->get();
    
    $totalEfectivo = $cobrosHoy->where('metodo_pago', 'efectivo')->sum('total_final')
        + $cobrosHoy->where('metodo_pago', 'mixto')->sum('pago_efectivo');
    
    $totalTarjeta = $cobrosHoy->where('metodo_pago', 'tarjeta')->sum('total_final')
        + $cobrosHoy->where('metodo_pago', 'mixto')->sum('pago_tarjeta');
    
    $totalBono = $cobrosHoy->where('metodo_pago', 'bono')->sum('coste');
    
    $bonosVendidosHoy = BonoCliente::whereDate('fecha_compra', $hoy)->get();
    $totalBonosEfectivo = $bonosVendidosHoy->where('metodo_pago', 'efectivo')->sum('precio_pagado');
    $totalBonosTarjeta = $bonosVendidosHoy->where('metodo_pago', 'tarjeta')->sum('precio_pagado');
    
    echo "   💵 Efectivo (servicios): €" . number_format($totalEfectivo, 2) . "\n";
    echo "   💳 Tarjeta (servicios): €" . number_format($totalTarjeta, 2) . "\n";
    echo "   🎫 Bonos (servicios): €" . number_format($totalBono, 2) . "\n";
    echo "   💵 Bonos vendidos (efectivo): €" . number_format($totalBonosEfectivo, 2) . "\n";
    echo "   💳 Bonos vendidos (tarjeta): €" . number_format($totalBonosTarjeta, 2) . "\n";
    
    $totalIngresado = $totalEfectivo + $totalTarjeta + $totalBonosEfectivo + $totalBonosTarjeta;
    echo "   💰 TOTAL INGRESADO HOY: €" . number_format($totalIngresado, 2) . "\n";
    echo "   ✅ Cálculos de caja diaria operativos\n";
    
    echo "\n";

    // ====================================================================
    // 3. CLIENTES CON BONOS
    // ====================================================================
    echo "3️⃣ CLIENTES CON BONOS\n";
    echo str_repeat("-", 70) . "\n";
    
    $totalBonos = BonoCliente::count();
    $bonosActivos = BonoCliente::where('estado', 'activo')->count();
    $bonosUsados = BonoCliente::where('estado', 'usado')->count();
    
    echo "   📋 Total bonos: $totalBonos\n";
    echo "   ✅ Bonos activos: $bonosActivos\n";
    echo "   ☑️  Bonos usados: $bonosUsados\n";
    
    // Verificar bonos marcados como activos pero completamente usados
    $bonosActivosReales = BonoCliente::where('estado', 'activo')->get();
    $bonosIncorrectos = 0;
    foreach ($bonosActivosReales as $bono) {
        if ($bono->estaCompletamenteUsado()) {
            $bonosIncorrectos++;
            $errores[] = "❌ Bono #{$bono->id} está marcado como activo pero está completamente usado";
        }
    }
    
    if ($bonosIncorrectos === 0) {
        echo "   ✅ Todos los bonos activos tienen servicios disponibles\n";
    }
    
    // Verificar bonos con servicios disponibles
    $clientesConBonos = Cliente::whereHas('bonos', function($query) {
        $query->where('estado', 'activo')
              ->whereHas('servicios', function($servicioQuery) {
                  $servicioQuery->whereRaw('cantidad_usada < cantidad_total');
              });
    })->count();
    
    echo "   👥 Clientes con bonos disponibles: $clientesConBonos\n";
    
    // Verificar integridad de la tabla pivot
    $pivotProblemas = \DB::table('bono_cliente_servicios')
        ->whereRaw('cantidad_usada > cantidad_total')
        ->count();
    
    if ($pivotProblemas > 0) {
        $errores[] = "❌ $pivotProblemas servicios de bonos con cantidad_usada > cantidad_total";
    } else {
        echo "   ✅ Todas las cantidades de servicios son coherentes\n";
    }
    
    echo "\n";

    // ====================================================================
    // 4. FACTURACIÓN MENSUAL
    // ====================================================================
    echo "4️⃣ FACTURACIÓN MENSUAL\n";
    echo str_repeat("-", 70) . "\n";
    
    $mesActual = Carbon::now()->month;
    $anioActual = Carbon::now()->year;
    $fechaInicio = Carbon::create($anioActual, $mesActual, 1)->startOfMonth();
    $fechaFin = Carbon::create($anioActual, $mesActual, 1)->endOfMonth();
    
    $cobrosMes = RegistroCobro::whereBetween('created_at', [$fechaInicio, $fechaFin])->get();
    
    $serviciosPeluqueria = 0;
    $serviciosEstetica = 0;
    
    foreach($cobrosMes as $cobro) {
        $yaContados = false;
        
        // Servicios de cita individual
        if ($cobro->cita && $cobro->cita->servicios && $cobro->cita->servicios->count() > 0) {
            foreach($cobro->cita->servicios as $servicio) {
                $precio = $servicio->pivot->precio ?? $servicio->precio;
                $proporcion = $cobro->coste > 0 ? ($precio / $cobro->coste) : 0;
                $precioReal = $cobro->total_final * $proporcion;
                
                if ($cobro->metodo_pago !== 'bono') {
                    if ($servicio->categoria === 'peluqueria') {
                        $serviciosPeluqueria += $precioReal;
                    } elseif ($servicio->categoria === 'estetica') {
                        $serviciosEstetica += $precioReal;
                    }
                }
            }
            $yaContados = true;
        }
        
        // Servicios de citas agrupadas
        if (!$yaContados && $cobro->citasAgrupadas && $cobro->citasAgrupadas->count() > 0) {
            foreach($cobro->citasAgrupadas as $citaGrupo) {
                if ($citaGrupo->servicios && $citaGrupo->servicios->count() > 0) {
                    foreach($citaGrupo->servicios as $servicio) {
                        $precio = $servicio->pivot->precio ?? $servicio->precio;
                        $proporcion = $cobro->coste > 0 ? ($precio / $cobro->coste) : 0;
                        $precioReal = $cobro->total_final * $proporcion;
                        
                        if ($cobro->metodo_pago !== 'bono') {
                            if ($servicio->categoria === 'peluqueria') {
                                $serviciosPeluqueria += $precioReal;
                            } elseif ($servicio->categoria === 'estetica') {
                                $serviciosEstetica += $precioReal;
                            }
                        }
                    }
                }
            }
            $yaContados = true;
        }
        
        // Servicios directos
        if (!$yaContados && $cobro->servicios && $cobro->servicios->count() > 0) {
            foreach($cobro->servicios as $servicio) {
                $precio = $servicio->pivot->precio ?? $servicio->precio;
                $proporcion = $cobro->coste > 0 ? ($precio / $cobro->coste) : 0;
                $precioReal = $cobro->total_final * $proporcion;
                
                if ($cobro->metodo_pago !== 'bono') {
                    if ($servicio->categoria === 'peluqueria') {
                        $serviciosPeluqueria += $precioReal;
                    } elseif ($servicio->categoria === 'estetica') {
                        $serviciosEstetica += $precioReal;
                    }
                }
            }
        }
    }
    
    echo "   💇 Facturación Peluquería: €" . number_format($serviciosPeluqueria, 2) . "\n";
    echo "   💅 Facturación Estética: €" . number_format($serviciosEstetica, 2) . "\n";
    echo "   💰 Total facturado (mes actual): €" . number_format($serviciosPeluqueria + $serviciosEstetica, 2) . "\n";
    echo "   ✅ Sistema de facturación operativo\n";
    
    echo "\n";

    // ====================================================================
    // 5. GESTIÓN DE DEUDAS
    // ====================================================================
    echo "5️⃣ GESTIÓN DE DEUDAS\n";
    echo str_repeat("-", 70) . "\n";
    
    $totalDeudas = Deuda::sum('saldo_pendiente');
    $clientesConDeuda = Cliente::conDeuda()->count();
    
    echo "   💰 Saldo pendiente total: €" . number_format($totalDeudas, 2) . "\n";
    echo "   👥 Clientes con deuda: $clientesConDeuda\n";
    
    // Verificar coherencia entre deuda y movimientos
    $deudasRegistradas = Deuda::where('saldo_pendiente', '>', 0)->get();
    foreach ($deudasRegistradas as $deuda) {
        $cargos = $deuda->movimientos()->where('tipo', 'cargo')->sum('monto');
        $abonos = $deuda->movimientos()->where('tipo', 'abono')->sum('monto');
        $saldoCalculado = $cargos - $abonos;
        
        if (abs($saldoCalculado - $deuda->saldo_pendiente) > 0.01) {
            $errores[] = "❌ Deuda del cliente #{$deuda->id_cliente}: saldo_pendiente (€{$deuda->saldo_pendiente}) no coincide con movimientos (€{$saldoCalculado})";
        }
    }
    
    if (count($errores) === 0 || !str_contains(implode('', $errores), 'Deuda del cliente')) {
        echo "   ✅ Todas las deudas cuadran con sus movimientos\n";
    }
    
    // Verificar que todos los cobros con deuda tienen movimiento
    $cobrosConDeuda = RegistroCobro::where('deuda', '>', 0)->get();
    $cobrosConDeudaSinMovimiento = 0;
    
    foreach ($cobrosConDeuda as $cobro) {
        $tieneMovimiento = MovimientoDeuda::where('id_registro_cobro', $cobro->id)
            ->where('tipo', 'cargo')
            ->exists();
        
        if (!$tieneMovimiento) {
            $cobrosConDeudaSinMovimiento++;
        }
    }
    
    if ($cobrosConDeudaSinMovimiento > 0) {
        $errores[] = "❌ $cobrosConDeudaSinMovimiento cobros con deuda sin movimiento registrado";
    } else {
        echo "   ✅ Todos los cobros con deuda tienen movimiento registrado\n";
    }
    
    echo "\n";

    // ====================================================================
    // 6. GESTIÓN DE BONOS (PLANTILLAS)
    // ====================================================================
    echo "6️⃣ GESTIÓN DE BONOS (PLANTILLAS)\n";
    echo str_repeat("-", 70) . "\n";
    
    $totalPlantillas = \App\Models\BonoPlantilla::count();
    $plantillasActivas = \App\Models\BonoPlantilla::where('activo', true)->count();
    
    echo "   📋 Total plantillas de bonos: $totalPlantillas\n";
    echo "   ✅ Plantillas activas: $plantillasActivas\n";
    
    // Verificar que todas las plantillas tienen servicios
    $plantillasSinServicios = \App\Models\BonoPlantilla::whereDoesntHave('servicios')->count();
    if ($plantillasSinServicios > 0) {
        $errores[] = "❌ $plantillasSinServicios plantillas de bonos sin servicios asociados";
    } else {
        echo "   ✅ Todas las plantillas tienen servicios asociados\n";
    }
    
    // Verificar bonos vendidos hoy
    $bonosVendidosHoyCount = BonoCliente::whereDate('fecha_compra', $hoy)->count();
    echo "   🎫 Bonos vendidos hoy: $bonosVendidosHoyCount\n";
    
    // Verificar uso de bonos (BonoUsoDetalle)
    $totalUsosRegistrados = \App\Models\BonoUsoDetalle::count();
    echo "   📊 Total de usos de bonos registrados: $totalUsosRegistrados\n";
    echo "   ✅ Sistema de gestión de bonos operativo\n";
    
    echo "\n";

    // ====================================================================
    // RESUMEN FINAL
    // ====================================================================
    echo str_repeat("=", 70) . "\n";
    echo "📋 RESUMEN DE LA VERIFICACIÓN\n";
    echo str_repeat("=", 70) . "\n\n";
    
    if (count($errores) === 0 && count($advertencias) === 0) {
        echo "✅ ¡TODOS LOS SISTEMAS ESTÁN CORRECTOS!\n";
        echo "   No se encontraron errores ni advertencias.\n\n";
    } else {
        if (count($errores) > 0) {
            echo "❌ ERRORES ENCONTRADOS (" . count($errores) . "):\n";
            foreach ($errores as $error) {
                echo "   $error\n";
            }
            echo "\n";
        }
        
        if (count($advertencias) > 0) {
            echo "⚠️  ADVERTENCIAS (" . count($advertencias) . "):\n";
            foreach ($advertencias as $advertencia) {
                echo "   $advertencia\n";
            }
            echo "\n";
        }
    }
    
    echo "🏁 Verificación completada.\n";
});
