<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class usersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insertar 2 users en la tabla users
        DB::table('users')->insert([
            [
                'nombre' => 'Juan',
                'apellidos' => 'Pérez',
                'telefono' => '123456789',
                'email' => 'juan.perez@correo.com',
                'password' => Hash::make('12345678'),
                'genero' => 'masculino',
                'edad' => 30,
                'rol' => 'empleado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Ana',
                'apellidos' => 'Gómez',
                'telefono' => '987654321',
                'email' => 'ana.gomez@correo.com',
                'password' => Hash::make('12345678'),
                'genero' => 'femenino',
                'edad' => 28,
                'rol' => 'empleado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Carlos',
                'apellidos' => 'Ramírez',
                'telefono' => '654321987',
                'email' => 'carlos.ramirez@correo.com',
                'password' => Hash::make('87654321'),
                'genero' => 'masculino',
                'edad' => 35,
                'rol' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
