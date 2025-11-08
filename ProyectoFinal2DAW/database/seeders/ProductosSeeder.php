<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('productos')->insert([
            [
                'nombre' => 'Champú Hidratante',
                'descripcion' => 'Champú profesional para cabello seco y dañado',
                'precio_venta' => 12.50,
                'precio_coste' => 6.00,
                'stock' => 50,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Acondicionador Reparador',
                'descripcion' => 'Acondicionador intensivo con keratina',
                'precio_venta' => 14.00,
                'precio_coste' => 7.00,
                'stock' => 45,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Mascarilla Capilar',
                'descripcion' => 'Tratamiento profundo para cabello maltratado',
                'precio_venta' => 18.00,
                'precio_coste' => 9.00,
                'stock' => 30,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Crema Hidratante Facial',
                'descripcion' => 'Crema antienvejecimiento con ácido hialurónico',
                'precio_venta' => 25.00,
                'precio_coste' => 12.00,
                'stock' => 40,
                'categoria' => 'estetica',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Esmalte de Uñas Premium',
                'descripcion' => 'Esmalte de larga duración, varios colores',
                'precio_venta' => 8.50,
                'precio_coste' => 3.50,
                'stock' => 100,
                'categoria' => 'estetica',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Aceite de Argán',
                'descripcion' => 'Aceite natural para cabello y piel, 100% orgánico',
                'precio_venta' => 22.00,
                'precio_coste' => 11.00,
                'stock' => 35,
                'categoria' => 'peluqueria',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
