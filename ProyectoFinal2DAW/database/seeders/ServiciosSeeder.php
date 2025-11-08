<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('servicios')->insert([
            [
                'nombre' => 'Corte de Cabello',
                'tiempo_estimado' => 30,
                'precio' => 15.00,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Tinte de Cabello',
                'tiempo_estimado' => 90,
                'precio' => 45.00,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Peinado y Brushing',
                'tiempo_estimado' => 45,
                'precio' => 20.00,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Manicura Completa',
                'tiempo_estimado' => 45,
                'precio' => 18.00,
                'categoria' => 'estetica',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Pedicura Completa',
                'tiempo_estimado' => 60,
                'precio' => 25.00,
                'categoria' => 'estetica',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Tratamiento Facial',
                'tiempo_estimado' => 60,
                'precio' => 35.00,
                'categoria' => 'estetica',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
