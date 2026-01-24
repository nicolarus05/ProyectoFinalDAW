#!/usr/bin/env php
<?php

// Determinar el directorio raíz del proyecto
$projectRoot = file_exists(__DIR__.'/vendor/autoload.php') 
    ? __DIR__ 
    : dirname(__DIR__);

require $projectRoot.'/vendor/autoload.php';
$app = require_once $projectRoot.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\RegistroCobro;
use App\Models\Empleado;
use Carbon\Carbon;

$tenant = Tenant::find('salonlh');
tenancy()->initialize($tenant);

$fechaInicio = Carbon::create(2026, 1, 1)->startOfMonth();
$fechaFin = Carbon::create(2026, 1, 1)->endOfMonth();

$lola = Empleado::with('user')->find(3);
echo "DIAGNÓSTICO DE FACTURACIÓN - " . $lola->user->nombre . " " . $lola->user->apellidos . PHP_EOL;
echo str_repeat("=", 80) . PHP_EOL . PHP_EOL;

$cobros = RegistroCobro::with(['servicios', 'productos', 'empleado.user', 'cliente.user'])
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->where('contabilizado', true)
    ->where('metodo_pago', '!=', 'bono')
    ->orderBy('id')
    ->get();

$totalServicios = 0;
$totalProductos = 0;

foreach ($cobros as $cobro) {
    $tieneServiciosLola = false;
    $tieneProductosLola = false;
    
    // Verificar servicios de Lola
    foreach ($cobro->servicios as $servicio) {
        if ($servicio->pivot->empleado_id == 3 && $servicio->pivot->precio > 0) {
            $tieneServiciosLola = true;
            break;
        }
    }
    
    // Verificar productos de Lola
    foreach ($cobro->productos as $producto) {
        $empleadoProducto = $producto->pivot->empleado_id ?? $cobro->id_empleado;
        if ($empleadoProducto == 3) {
            $tieneProductosLola = true;
            break;
        }
    }
    
    if ($tieneServiciosLola || $tieneProductosLola) {
        echo "COBRO #{$cobro->id}" . PHP_EOL;
        echo "Fecha: " . $cobro->created_at->format('d/m/Y H:i') . PHP_EOL;
        echo "Cliente: " . ($cobro->cliente->user->nombre ?? 'N/A') . PHP_EOL;
        echo "Empleado del cobro: " . $cobro->empleado->user->nombre . PHP_EOL;
        echo "Coste inicial: €{$cobro->coste}" . PHP_EOL;
        
        $tieneDescuento = ($cobro->descuento_porcentaje > 0 || $cobro->descuento_euro > 0);
        if ($tieneDescuento) {
            echo "⚠️ DESCUENTO APLICADO: ";
            if ($cobro->descuento_porcentaje > 0) {
                echo "{$cobro->descuento_porcentaje}%";
            }
            if ($cobro->descuento_euro > 0) {
                echo " €{$cobro->descuento_euro}";
            }
            echo PHP_EOL;
        }
        
        echo "Total final: €{$cobro->total_final}" . PHP_EOL;
        echo "Deuda: €{$cobro->deuda}" . PHP_EOL;
        echo "Dinero cliente: €{$cobro->dinero_cliente}" . PHP_EOL;
        echo PHP_EOL;
        
        if ($tieneServiciosLola) {
            echo "  SERVICIOS DE LOLA:" . PHP_EOL;
            foreach ($cobro->servicios as $servicio) {
                if ($servicio->pivot->empleado_id == 3) {
                    $precio = $servicio->pivot->precio;
                    if ($precio > 0) {
                        echo "    ✓ FACTURADO: {$servicio->nombre} - €{$precio}" . PHP_EOL;
                        $totalServicios += $precio;
                    } else {
                        echo "    ✗ NO FACTURADO (precio=0, pagado con bono): {$servicio->nombre}" . PHP_EOL;
                    }
                }
            }
            echo PHP_EOL;
        }
        
        if ($tieneProductosLola) {
            echo "  PRODUCTOS DE LOLA:" . PHP_EOL;
            foreach ($cobro->productos as $producto) {
                $empleadoProducto = $producto->pivot->empleado_id ?? $cobro->id_empleado;
                if ($empleadoProducto == 3) {
                    $subtotal = $producto->pivot->subtotal;
                    $cantidad = $producto->pivot->cantidad;
                    $precioUnit = $producto->pivot->precio_unitario;
                    echo "    ✓ {$producto->nombre} x{$cantidad} (€{$precioUnit} c/u) - €{$subtotal}" . PHP_EOL;
                    $totalProductos += $subtotal;
                }
            }
            echo PHP_EOL;
        }
        
        echo str_repeat("-", 80) . PHP_EOL . PHP_EOL;
    }
}

echo str_repeat("=", 80) . PHP_EOL;
echo "RESUMEN FINAL:" . PHP_EOL;
echo "Total SERVICIOS facturados a Lola: €{$totalServicios}" . PHP_EOL;
echo "Total PRODUCTOS facturados a Lola: €{$totalProductos}" . PHP_EOL;
echo "TOTAL: €" . ($totalServicios + $totalProductos) . PHP_EOL;
echo PHP_EOL;

// Contar cobros con descuento
$cobrosConDescuento = $cobros->filter(function($cobro) {
    foreach ($cobro->servicios as $servicio) {
        if ($servicio->pivot->empleado_id == 3 && $servicio->pivot->precio > 0) {
            return true;
        }
    }
    foreach ($cobro->productos as $producto) {
        $empleadoProducto = $producto->pivot->empleado_id ?? $cobro->id_empleado;
        if ($empleadoProducto == 3) {
            return true;
        }
    }
    return false;
})->filter(function($cobro) {
    return ($cobro->descuento_porcentaje > 0 || $cobro->descuento_euro > 0);
});

if ($cobrosConDescuento->count() > 0) {
    echo str_repeat("=", 80) . PHP_EOL;
    echo "⚠️ COBROS CON DESCUENTO DE LOLA:" . PHP_EOL;
    echo str_repeat("=", 80) . PHP_EOL . PHP_EOL;
    
    foreach ($cobrosConDescuento as $cobro) {
        echo "Cobro #{$cobro->id}:" . PHP_EOL;
        echo "  Coste inicial: €{$cobro->coste}" . PHP_EOL;
        if ($cobro->descuento_porcentaje > 0) {
            echo "  Descuento: {$cobro->descuento_porcentaje}%" . PHP_EOL;
        }
        if ($cobro->descuento_euro > 0) {
            echo "  Descuento: €{$cobro->descuento_euro}" . PHP_EOL;
        }
        echo "  Total final: €{$cobro->total_final}" . PHP_EOL;
        echo "  Diferencia: €" . ($cobro->coste - $cobro->total_final) . PHP_EOL;
        echo PHP_EOL;
    }
    echo "⚠️ IMPORTANTE: Los descuentos se aplican ANTES de facturar." . PHP_EOL;
    echo "   Los empleados facturan sobre el precio CON descuento (precios del pivot)." . PHP_EOL;
    echo PHP_EOL;
}

// Verificar cobros pagados con BONO
echo str_repeat("=", 80) . PHP_EOL;
echo "COBROS PAGADOS CON BONO (método_pago = 'bono'):" . PHP_EOL;
echo str_repeat("=", 80) . PHP_EOL . PHP_EOL;

$cobrosBono = RegistroCobro::with(['servicios', 'productos', 'empleado.user', 'cliente.user'])
    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
    ->where('metodo_pago', '=', 'bono')
    ->where('contabilizado', true)
    ->orderBy('id')
    ->get();

if ($cobrosBono->count() > 0) {
    foreach ($cobrosBono as $cobro) {
        $tieneServiciosLola = false;
        foreach ($cobro->servicios as $servicio) {
            if ($servicio->pivot->empleado_id == 3) {
                $tieneServiciosLola = true;
                break;
            }
        }
        
        if ($tieneServiciosLola) {
            echo "COBRO #{$cobro->id} - PAGADO CON BONO" . PHP_EOL;
            echo "Fecha: " . $cobro->created_at->format('d/m/Y H:i') . PHP_EOL;
            echo "Cliente: " . ($cobro->cliente->user->nombre ?? 'N/A') . PHP_EOL;
            echo "Empleado del cobro: " . $cobro->empleado->user->nombre . PHP_EOL;
            echo "Contabilizado: " . ($cobro->contabilizado ? 'SÍ' : 'NO') . PHP_EOL;
            echo PHP_EOL;
            
            echo "  SERVICIOS DE LOLA:" . PHP_EOL;
            foreach ($cobro->servicios as $servicio) {
                if ($servicio->pivot->empleado_id == 3) {
                    $precio = $servicio->pivot->precio;
                    if ($precio > 0) {
                        echo "    ⚠️ ERROR: Servicio con precio > 0 en cobro BONO: {$servicio->nombre} - €{$precio}" . PHP_EOL;
                    } else {
                        echo "    ✓ CORRECTO (precio=0): {$servicio->nombre}" . PHP_EOL;
                    }
                }
            }
            echo PHP_EOL;
            echo str_repeat("-", 80) . PHP_EOL . PHP_EOL;
        }
    }
} else {
    echo "No hay cobros pagados con bono de Lola en este periodo." . PHP_EOL;
}

