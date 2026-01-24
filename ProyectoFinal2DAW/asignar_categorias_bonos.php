<?php

use Illuminate\Support\Facades\DB;
use App\Models\BonoPlantilla;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

tenancy()->initialize('salonlh');

echo "\n" . str_repeat('=', 80) . "\n";
echo "VERIFICACIÓN Y ASIGNACIÓN DE CATEGORÍAS A BONOS\n";
echo str_repeat('=', 80) . "\n\n";

// Buscar bonos sin categoría
$bonosSinCategoria = BonoPlantilla::where(function($query) {
    $query->whereNull('categoria')
          ->orWhere('categoria', '');
})->get();

if ($bonosSinCategoria->count() == 0) {
    echo "✅ Todos los bonos tienen categoría asignada\n\n";
    exit(0);
}

echo "Bonos sin categoría encontrados: {$bonosSinCategoria->count()}\n";
echo str_repeat('-', 80) . "\n\n";

foreach ($bonosSinCategoria as $bono) {
    echo "Bono ID: {$bono->id}\n";
    echo "Nombre: {$bono->nombre}\n";
    echo "Descripción: " . ($bono->descripcion ?? 'N/A') . "\n";
    echo "Precio: {$bono->precio}€\n";
    
    // Cargar servicios del bono para inferir categoría
    $bono->load('servicios');
    
    if ($bono->servicios->count() > 0) {
        echo "Servicios incluidos ({$bono->servicios->count()}):\n";
        
        $categorias = [];
        foreach ($bono->servicios as $servicio) {
            echo "  - {$servicio->nombre} (categoria: " . ($servicio->categoria ?? 'N/A') . ")\n";
            if ($servicio->categoria) {
                $categorias[] = $servicio->categoria;
            }
        }
        
        // Inferir categoría del bono basándose en los servicios
        if (!empty($categorias)) {
            $categoriaMasFrecuente = array_count_values($categorias);
            arsort($categoriaMasFrecuente);
            $categoriaInferida = array_key_first($categoriaMasFrecuente);
            
            echo "\nCategoría inferida: {$categoriaInferida}\n";
            echo "¿Asignar esta categoría? (s/n): ";
            
            // En un script automatizado, asignamos la categoría más frecuente
            $bono->categoria = $categoriaInferida;
            $bono->save();
            
            echo "✅ Categoría '{$categoriaInferida}' asignada automáticamente\n";
        } else {
            echo "\n⚠️ No se pudo inferir categoría (servicios sin categoría)\n";
            echo "Asignando 'peluqueria' por defecto\n";
            $bono->categoria = 'peluqueria';
            $bono->save();
            echo "✅ Categoría 'peluqueria' asignada por defecto\n";
        }
    } else {
        echo "No tiene servicios asociados\n";
        echo "Asignando 'peluqueria' por defecto\n";
        $bono->categoria = 'peluqueria';
        $bono->save();
        echo "✅ Categoría 'peluqueria' asignada por defecto\n";
    }
    
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

echo "✅ Proceso completado\n";
echo "Total bonos actualizados: {$bonosSinCategoria->count()}\n\n";
