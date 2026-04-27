<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subcategoria;

class SubcategoriasSeeder extends Seeder
{
    public function run(): void
    {
        $subcategorias = [
            // ── Peluquería ──────────────────────────────────────────────────
            ['nombre' => 'Cortes',                'categoria' => 'peluqueria', 'color' => '#8b5cf6'],
            ['nombre' => 'Peinados',              'categoria' => 'peluqueria', 'color' => '#f97316'],
            ['nombre' => 'Tintes y mechas',       'categoria' => 'peluqueria', 'color' => '#ec4899'],
            ['nombre' => 'Secados',               'categoria' => 'peluqueria', 'color' => '#3b82f6'],
            ['nombre' => 'Tratamientos capilares','categoria' => 'peluqueria', 'color' => '#22c55e'],
            ['nombre' => 'Lavados',               'categoria' => 'peluqueria', 'color' => '#06b6d4'],
            // ── Estética ────────────────────────────────────────────────────
            ['nombre' => 'Manicura y pedicura',   'categoria' => 'estetica',   'color' => '#f43f5e'],
            ['nombre' => 'Faciales',              'categoria' => 'estetica',   'color' => '#f59e0b'],
            ['nombre' => 'Depilación',            'categoria' => 'estetica',   'color' => '#a855f7'],
            ['nombre' => 'Masajes',               'categoria' => 'estetica',   'color' => '#10b981'],
            ['nombre' => 'Maquillaje',            'categoria' => 'estetica',   'color' => '#e11d48'],
        ];

        foreach ($subcategorias as $sub) {
            Subcategoria::firstOrCreate(
                ['nombre' => $sub['nombre'], 'categoria' => $sub['categoria']],
                ['color' => $sub['color'], 'activo' => true]
            );
        }
    }
}
