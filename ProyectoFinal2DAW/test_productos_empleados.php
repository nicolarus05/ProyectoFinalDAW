<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\RegistroCobro;
use App\Models\Empleado;
use App\Models\Productos;
use App\Services\FacturacionService;
use Illuminate\Support\Facades\DB;

$tenant = Tenant::find('salonlh');
if($tenant) {
    tenancy()->initialize($tenant);
    
    echo PHP_EOL . "═══════════════════════════════════════════════════════════════" . PHP_EOL;
    echo "  PRUEBA DE PRODUCTOS CON MÚLTIPLES EMPLEADOS" . PHP_EOL;
    echo "═══════════════════════════════════════════════════════════════" . PHP_EOL . PHP_EOL;
    
    // Obtener empleados
    $empleados = Empleado::with('user')->take(3)->get();
    
    echo "Empleados disponibles:" . PHP_EOL;
    foreach($empleados as $emp) {
        echo "  - ID {$emp->id}: {$emp->user->nombre} {$emp->user->apellidos}" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Obtener productos
    $productos = Productos::where('stock', '>', 0)->take(3)->get();
    
    if($productos->count() < 3) {
        echo "⚠️  No hay suficientes productos con stock. Creando productos de prueba..." . PHP_EOL;
        
        for($i = 1; $i <= 3; $i++) {
            Productos::create([
                'nombre' => "Producto Test $i",
                'precio_venta' => 10.00 * $i,
                'precio_compra' => 5.00 * $i,
                'stock' => 100,
                'tipo' => 'producto'
            ]);
        }
        
        $productos = Productos::where('nombre', 'LIKE', 'Producto Test%')->get();
    }
    
    echo "Productos disponibles:" . PHP_EOL;
    foreach($productos as $prod) {
        echo "  - ID {$prod->id}: {$prod->nombre} - €{$prod->precio_venta} (Stock: {$prod->stock})" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Crear cobro de prueba
    echo "Creando cobro de prueba..." . PHP_EOL;
    
    DB::beginTransaction();
    
    try {
        $cobro = RegistroCobro::create([
            'coste' => 0,
            'total_final' => 60.00,
            'total_bonos_vendidos' => 0,
            'dinero_cliente' => 60.00,
            'deuda' => 0,
            'metodo_pago' => 'efectivo',
            'id_empleado' => $empleados[0]->id,
            'contabilizado' => true,
        ]);
        
        echo "✅ Cobro #{$cobro->id} creado" . PHP_EOL . PHP_EOL;
        
        // Añadir productos con diferentes empleados
        echo "Añadiendo productos con diferentes empleados:" . PHP_EOL;
        
        foreach($productos as $index => $producto) {
            $empleado = $empleados[$index % count($empleados)];
            $cantidad = 1;
            $subtotal = $producto->precio_venta * $cantidad;
            
            $cobro->productos()->attach($producto->id, [
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->precio_venta,
                'subtotal' => $subtotal,
                'empleado_id' => $empleado->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "  ✓ {$producto->nombre} → {$empleado->user->nombre} (€{$subtotal})" . PHP_EOL;
        }
        
        DB::commit();
        echo PHP_EOL . "✅ Cobro guardado correctamente" . PHP_EOL . PHP_EOL;
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
    
    // Recargar el cobro con relaciones
    $cobro->load('productos');
    
    // Verificar usando FacturacionService
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
    echo "VERIFICACIÓN CON FacturacionService" . PHP_EOL;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL . PHP_EOL;
    
    $service = new FacturacionService();
    $desglose = $service->desglosarCobroPorEmpleado($cobro);
    
    $totalGeneral = 0;
    foreach($desglose as $empId => $datos) {
        if($datos['total'] > 0) {
            $emp = Empleado::with('user')->find($empId);
            $nombre = $emp ? $emp->user->nombre . ' ' . $emp->user->apellidos : 'Empleado #' . $empId;
            
            echo "👤 {$nombre} (ID: {$empId}):" . PHP_EOL;
            echo "    Productos: €" . number_format($datos['productos'], 2) . PHP_EOL;
            echo "    TOTAL: €" . number_format($datos['total'], 2) . PHP_EOL;
            echo PHP_EOL;
            
            $totalGeneral += $datos['total'];
        }
    }
    
    echo "TOTAL FACTURADO: €" . number_format($totalGeneral, 2) . PHP_EOL;
    echo PHP_EOL;
    
    // Verificación manual desde pivot
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
    echo "VERIFICACIÓN MANUAL DESDE PIVOT" . PHP_EOL;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL . PHP_EOL;
    
    $productosPorEmpleado = [];
    
    foreach($cobro->productos as $producto) {
        $empId = $producto->pivot->empleado_id;
        $subtotal = $producto->pivot->subtotal;
        
        if(!isset($productosPorEmpleado[$empId])) {
            $emp = Empleado::with('user')->find($empId);
            $productosPorEmpleado[$empId] = [
                'nombre' => $emp ? $emp->user->nombre . ' ' . $emp->user->apellidos : 'Empleado #' . $empId,
                'total' => 0,
                'productos' => []
            ];
        }
        
        $productosPorEmpleado[$empId]['total'] += $subtotal;
        $productosPorEmpleado[$empId]['productos'][] = [
            'nombre' => $producto->nombre,
            'subtotal' => $subtotal
        ];
    }
    
    foreach($productosPorEmpleado as $empId => $data) {
        echo "👤 {$data['nombre']} (ID: {$empId}):" . PHP_EOL;
        foreach($data['productos'] as $p) {
            echo "    • {$p['nombre']}: €" . number_format($p['subtotal'], 2) . PHP_EOL;
        }
        echo "    TOTAL: €" . number_format($data['total'], 2) . PHP_EOL;
        echo PHP_EOL;
    }
    
    // Comparación
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL;
    echo "RESULTADO" . PHP_EOL;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . PHP_EOL . PHP_EOL;
    
    $todoCorrecto = true;
    
    foreach($productosPorEmpleado as $empId => $dataManual) {
        $totalService = isset($desglose[$empId]) ? $desglose[$empId]['productos'] : 0;
        $totalManual = $dataManual['total'];
        $diferencia = abs($totalService - $totalManual);
        
        if($diferencia > 0.01) {
            echo "❌ {$dataManual['nombre']}: Diferencia de €" . number_format($diferencia, 2) . PHP_EOL;
            echo "   FacturacionService: €{$totalService}" . PHP_EOL;
            echo "   Manual (pivot): €{$totalManual}" . PHP_EOL;
            $todoCorrecto = false;
        } else {
            echo "✅ {$dataManual['nombre']}: Facturación correcta (€" . number_format($totalManual, 2) . ")" . PHP_EOL;
        }
    }
    
    echo PHP_EOL;
    
    if($todoCorrecto) {
        echo "═══════════════════════════════════════════════════════════════" . PHP_EOL;
        echo "✅✅✅ PRUEBA EXITOSA ✅✅✅" . PHP_EOL;
        echo "═══════════════════════════════════════════════════════════════" . PHP_EOL;
        echo "Cada empleado factura SOLO los productos que tiene asignados" . PHP_EOL;
        echo "La implementación funciona correctamente" . PHP_EOL;
        echo PHP_EOL;
        exit(0);
    } else {
        echo "═══════════════════════════════════════════════════════════════" . PHP_EOL;
        echo "❌❌❌ PRUEBA FALLIDA ❌❌❌" . PHP_EOL;
        echo "═══════════════════════════════════════════════════════════════" . PHP_EOL;
        exit(1);
    }
}

echo "❌ No se encontró el tenant salonlh" . PHP_EOL;
exit(1);
