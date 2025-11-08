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
        // Insertar 2 empleados asociados a los users
        DB::table('empleados')->insert([
            [
                'id_user' => 1,  // ID del user Juan Pérez
                'categoria' => 'peluqueria',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => 2,  // ID del user Ana Gómez
                'categoria' => 'estetica',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
