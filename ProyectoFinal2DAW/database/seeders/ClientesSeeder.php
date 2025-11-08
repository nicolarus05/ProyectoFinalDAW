<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientesSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = [];
        
        $users = [
            [
                'nombre' => 'María',
                'apellidos' => 'González López',
                'email' => 'maria.gonzalez@email.com',
                'password' => Hash::make('password123'),
                'telefono' => '612345678',
                'edad' => 28,
                'genero' => 'femenino',
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Pedro',
                'apellidos' => 'Martínez Ruiz',
                'email' => 'pedro.martinez@email.com',
                'password' => Hash::make('password123'),
                'telefono' => '623456789',
                'edad' => 35,
                'genero' => 'masculino',
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Laura',
                'apellidos' => 'Fernández García',
                'email' => 'laura.fernandez@email.com',
                'password' => Hash::make('password123'),
                'telefono' => '634567890',
                'edad' => 42,
                'genero' => 'femenino',
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Javier',
                'apellidos' => 'Sánchez Díaz',
                'email' => 'javier.sanchez@email.com',
                'password' => Hash::make('password123'),
                'telefono' => '645678901',
                'edad' => 31,
                'genero' => 'masculino',
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Carmen',
                'apellidos' => 'Rodríguez Moreno',
                'email' => 'carmen.rodriguez@email.com',
                'password' => Hash::make('password123'),
                'telefono' => '656789012',
                'edad' => 26,
                'genero' => 'femenino',
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Antonio',
                'apellidos' => 'López Jiménez',
                'email' => 'antonio.lopez@email.com',
                'password' => Hash::make('password123'),
                'telefono' => '667890123',
                'edad' => 38,
                'genero' => 'masculino',
                'rol' => 'cliente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $direcciones = [
            'Calle Mayor 15, Madrid',
            'Avenida Libertad 23, Barcelona',
            'Plaza España 8, Valencia',
            'Calle Sol 45, Sevilla',
            'Ronda Norte 12, Bilbao',
            'Paseo Gracia 67, Zaragoza',
        ];

        foreach ($users as $index => $userData) {
            $userId = DB::table('users')->insertGetId($userData);
            $userIds[] = [
                'id' => $userId,
                'direccion' => $direcciones[$index]
            ];
        }

        $clientes = [];
        foreach ($userIds as $data) {
            $clientes[] = [
                'id_user' => $data['id'],
                'direccion' => $data['direccion'],
                'fecha_registro' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('clientes')->insert($clientes);
    }
}
