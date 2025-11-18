<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tenants = ['tenantredireccion', 'tenantsalon4', 'tenantsalon5', 'tenantsalongarcia', 'tenantsalonlh'];

foreach ($tenants as $tenant) {
    echo "\n=== Limpiando tenant: $tenant ===\n";
    
    try {
        // Borrar detalles de citas primero (foreign keys)
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.detalles_cita");
        echo "Detalles de citas eliminados: $deleted\n";
        
        // Borrar registros de cobro relacionados con citas
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.registros_cobro WHERE id_cita IS NOT NULL");
        echo "Registros de cobro de citas eliminados: $deleted\n";
        
        // Borrar citas
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.citas");
        echo "Citas eliminadas: $deleted\n";
        
        // Borrar horarios de trabajo
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.horario_trabajo");
        echo "Horarios eliminados: $deleted\n";
        
        // Obtener IDs de usuarios de clientes
        $userIds = DB::connection('mysql')->select("SELECT id_user FROM {$tenant}.clientes");
        $userIdsList = array_column($userIds, 'id_user');
        
        // Borrar deudas de clientes
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.deudas");
        echo "Deudas eliminadas: $deleted\n";
        
        // Borrar bonos de clientes
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.bono_cliente");
        echo "Bonos de clientes eliminados: $deleted\n";
        
        // Borrar clientes
        $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.clientes");
        echo "Clientes eliminados: $deleted\n";
        
        // Borrar usuarios de clientes
        if (!empty($userIdsList)) {
            $ids = implode(',', $userIdsList);
            $deleted = DB::connection('mysql')->delete("DELETE FROM {$tenant}.users WHERE id IN ($ids) AND rol = 'cliente'");
            echo "Usuarios de clientes eliminados: $deleted\n";
        }
        
        echo "✅ Tenant $tenant limpiado correctamente\n";
        
    } catch (\Exception $e) {
        echo "❌ Error en tenant $tenant: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Limpieza completada en todos los tenants\n";
