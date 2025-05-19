<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpleadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar 2 empleados asociados a los usuarios
        DB::table('empleados')->insert([
            [
                'id_usuario' => 1,  // ID del usuario Juan Pérez
                'especializacion' => 'Peluquero',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_usuario' => 2,  // ID del usuario Ana Gómez
                'especializacion' => 'Esteticista',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
