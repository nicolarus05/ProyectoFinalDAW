<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiciosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{
        DB::table('servicios')->insert([
            [
                'nombre' => 'Corte de Cabello',
                'precio' => 15.00,
                'tiempo_estimado' => 30,
                'tipo' => 'peluqueria'
            ],
            [
                'nombre' => 'Manicura',
                'precio' => 12.00,
                'tiempo_estimado' => 45,
                'tipo' => 'estetica'
            ],
            [
                'nombre' => 'Tinte de Cabello',
                'precio' => 25.00,
                'tiempo_estimado' => 60,
                'tipo' => 'peluqueria'
            ],
            [
                'nombre' => 'Limpieza Facial',
                'precio' => 20.00,
                'tiempo_estimado' => 50,
                'tipo' => 'estetica'
            ],
        ]);
    }
}
