<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

$tenant = Tenant::find('salonlh');
$tenant->run(function() {
    echo "\n=== VERIFICACIÓN FACTURACIÓN LOLA (EMPLEADO 4) ===\n\n";
    
    // Ver todas las citas
    $citas = DB::table('citas')
        ->where('id_empleado', 4)
        ->whereBetween('fecha_hora', ['2026-01-01', '2026-01-31 23:59:59'])
        ->orderBy('fecha_hora')
        ->get();
    
    echo "Total citas de Lola en enero: " . $citas->count() . "\n\n";
    
    $totalEsperado = 0;
    $citasSinCobro = 0;
    $citasConCobroSinRegistro = [];
    
    foreach($citas as $cita) {
        // Calcular costo de servicios de la cita
        $servicios = DB::table('cita_servicio')
            ->where('cita_id', $cita->id)
            ->sum('precio');
        
        $totalEsperado += $servicios;
        
        // Ver si tiene cobro
        $cobro = DB::table('registro_cobros')->where('id_cita', $cita->id)->first();
        
        if(!$cobro) {
            $citasSinCobro++;
            echo "❌ Cita #{$cita->id} ({$cita->fecha_hora}): Servicios {$servicios}€ - SIN COBRO\n";
        } else {
            // Ver si el cobro tiene servicios en registro_cobro_servicio
            $tieneEnRegistro = DB::table('registro_cobro_servicio')
                ->where('registro_cobro_id', $cobro->id)
                ->where('empleado_id', 4)
                ->exists();
            
            if(!$tieneEnRegistro && $cobro->metodo_pago !== 'bono') {
                $citasConCobroSinRegistro[] = [
                    'cita_id' => $cita->id,
                    'cobro_id' => $cobro->id,
                    'metodo_pago' => $cobro->metodo_pago,
                    'total' => $cobro->total_final,
                    'fecha' => $cita->fecha_hora
                ];
                echo "⚠️  Cita #{$cita->id} ({$cita->fecha_hora}): Cobro #{$cobro->id} ({$cobro->metodo_pago}, {$cobro->total_final}€) - SIN SERVICIOS EN REGISTRO\n";
            }
        }
    }
    
    echo "\n==========================================\n";
    echo "RESUMEN:\n";
    echo "  • Total citas: " . $citas->count() . "\n";
    echo "  • Citas sin cobro: {$citasSinCobro}\n";
    echo "  • Citas con cobro sin registro: " . count($citasConCobroSinRegistro) . "\n";
    echo "  • Total esperado (suma servicios): {$totalEsperado}€\n";
    
    // Calcular facturación actual
    $facturacionActual = DB::table('registro_cobro_servicio')
        ->join('registro_cobros', 'registro_cobro_servicio.registro_cobro_id', '=', 'registro_cobros.id')
        ->where('registro_cobro_servicio.empleado_id', 4)
        ->whereBetween('registro_cobros.created_at', ['2026-01-01', '2026-01-31 23:59:59'])
        ->sum('registro_cobro_servicio.precio');
    
    echo "  • Facturación actual (registro_cobro_servicio): {$facturacionActual}€\n";
    echo "==========================================\n";
    
    if(count($citasConCobroSinRegistro) > 0) {
        echo "\n🔧 CITAS QUE NECESITAN CORRECCIÓN:\n";
        foreach($citasConCobroSinRegistro as $problema) {
            echo "  - Cita #{$problema['cita_id']} -> Cobro #{$problema['cobro_id']}\n";
        }
    }
});
