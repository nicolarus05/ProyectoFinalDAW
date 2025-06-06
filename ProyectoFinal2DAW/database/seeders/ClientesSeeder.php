<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar 2 clientes asociados a los users
        DB::table('clientes')->insert([
            [
                'id_user' => 1,  // ID del user Juan Pérez
                'direccion' => 'Calle Falsa 123, Ciudad X',
                'notas_adicionales' => 'Cliente frecuente.',
                'fecha_registro' => Carbon::now()->subDays(15), // Fecha de registro hace 15 días
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => 2,  // ID del user Ana Gómez
                'direccion' => 'Avenida Siempre Viva 456, Ciudad Y',
                'notas_adicionales' => 'Primera vez que visita.',
                'fecha_registro' => Carbon::now()->subDays(30), // Fecha de registro hace 30 días
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
