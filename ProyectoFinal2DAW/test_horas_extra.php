<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Obtener el primer tenant
$tenant = Stancl\Tenancy\Database\Models\Tenant::first();

if (!$tenant) {
    echo "❌ No hay tenants en la base de datos\n";
    exit(1);
}

echo "🏢 Tenant: {$tenant->id}\n\n";

tenancy()->initialize($tenant);

// Obtener el primer empleado
$empleado = App\Models\Empleado::with('user')->first();
    
    if (!$empleado) {
        echo "❌ No hay empleados en la base de datos\n";
        return;
    }
    
    echo "✓ Empleado: {$empleado->user->nombre} {$empleado->user->apellidos}\n";
    
    // Buscar horario de hoy del empleado
    $hoy = Carbon\Carbon::today();
    $horario = App\Models\HorarioTrabajo::where('id_empleado', $empleado->id)
        ->whereDate('fecha', $hoy)
        ->first();
    
    if (!$horario) {
        echo "⚠️  No hay horario definido para hoy\n";
        echo "📅 Creando horario de prueba (09:00 - 13:30)...\n";
        $horario = App\Models\HorarioTrabajo::create([
            'id_empleado' => $empleado->id,
            'fecha' => $hoy,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '13:30:00',
            'hora' => null,
            'disponible' => true
        ]);
    }
    
    echo "✓ Horario: {$horario->hora_inicio} - {$horario->hora_fin}\n\n";
    
    // Crear registro de entrada/salida de prueba con salida TARDE
    $entrada = Carbon\Carbon::parse($hoy->format('Y-m-d') . ' 09:00:00');
    $salidaTarde = Carbon\Carbon::parse($hoy->format('Y-m-d') . ' 14:30:00'); // 1 hora tarde (debería salir a las 13:30)
    
    // Calcular minutos extra
    $horaSalidaProgramada = Carbon\Carbon::parse($hoy->format('Y-m-d') . ' ' . $horario->hora_fin);
    $minutosExtra = $salidaTarde->diffInMinutes($horaSalidaProgramada);
    
    $registro = App\Models\RegistroEntradaSalida::create([
        'id_empleado' => $empleado->id,
        'fecha' => $hoy,
        'hora_entrada' => $entrada->format('H:i:s'),
        'hora_salida' => $salidaTarde->format('H:i:s'),
        'salida_fuera_horario' => true,
        'minutos_extra' => $minutosExtra
    ]);
    
    echo "✅ REGISTRO DE PRUEBA CREADO:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 ID: {$registro->id}\n";
    echo "🕐 Entrada: {$registro->hora_entrada}\n";
    echo "🕐 Salida: {$registro->hora_salida}\n";
    echo "⚠️  Salida fuera de horario: " . ($registro->salida_fuera_horario ? '✅ SÍ' : '❌ NO') . "\n";
    echo "⏱️  Minutos extra: {$registro->minutos_extra} minutos\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $horasTrabajadas = $registro->calcularHorasTrabajadas();
    echo "💼 Total trabajado: {$horasTrabajadas['formatted']}\n\n";
    
    echo "🔍 Para verificar en la interfaz:\n";
    echo "   Ve a: /asistencia?fecha={$hoy->format('Y-m-d')}\n";
    echo "   Deberías ver:\n";
    echo "   - Fondo amarillo en la fila\n";
    echo "   - ⚠️ junto al nombre del empleado\n";
    echo "   - '+{$minutosExtra} min' bajo la hora de salida\n\n";
    
    echo "🗑️  Para eliminar este registro de prueba:\n";
    echo "   php artisan tinker --execute=\"\App\Models\RegistroEntradaSalida::find({$registro->id})->delete();\"\n";
