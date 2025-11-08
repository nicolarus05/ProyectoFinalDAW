<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BonosSeeder extends Seeder
{
    public function run(): void
    {
        // Verificar si ya existen plantillas para no duplicar
        $existenPlantillas = DB::table('bonos_plantilla')->count() > 0;
        
        if (!$existenPlantillas) {
            // Crear plantillas de bonos
            $plantillas = [
            [
                'nombre' => 'Bono 5 Cortes',
                'descripcion' => 'Bono de 5 cortes de cabello con descuento',
                'precio' => 65.00,
                'duracion_dias' => 180,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Bono 10 Manicuras',
                'descripcion' => 'Bono de 10 sesiones de manicura',
                'precio' => 150.00,
                'duracion_dias' => 365,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Bono Mixto Premium',
                'descripcion' => 'Bono combinado: 3 cortes + 3 manicuras + 2 faciales',
                'precio' => 180.00,
                'duracion_dias' => 270,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Bono Pedicura Mensual',
                'descripcion' => 'Bono de 6 sesiones de pedicura (mensual durante 6 meses)',
                'precio' => 130.00,
                'duracion_dias' => 180,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Bono Tratamiento Facial',
                'descripcion' => 'Bono de 4 tratamientos faciales completos',
                'precio' => 120.00,
                'duracion_dias' => 120,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Bono Anual VIP',
                'descripcion' => 'Bono anual con acceso ilimitado a todos los servicios básicos',
                'precio' => 250.00,
                'duracion_dias' => 365,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('bonos_plantilla')->insert($plantillas);
        }

        // Obtener IDs de clientes y plantillas
        $clienteIds = DB::table('clientes')->pluck('id')->toArray();
        $plantillaIds = DB::table('bonos_plantilla')->pluck('id')->toArray();
        $empleadoId = DB::table('empleados')->first()->id;

        // Crear bonos activos para clientes
        $bonos = [];
        foreach ($clienteIds as $index => $clienteId) {
            if (isset($plantillaIds[$index])) {
                $fechaCompra = Carbon::now()->subDays(rand(10, 60));
                $plantilla = DB::table('bonos_plantilla')->find($plantillaIds[$index]);
                
                $bonos[] = [
                    'cliente_id' => $clienteId,
                    'bono_plantilla_id' => $plantillaIds[$index],
                    'fecha_compra' => $fechaCompra,
                    'fecha_expiracion' => $fechaCompra->copy()->addDays($plantilla->duracion_dias),
                    'estado' => 'activo',
                    'metodo_pago' => ['efectivo', 'tarjeta'][rand(0, 1)],
                    'precio_pagado' => $plantilla->precio,
                    'dinero_cliente' => $plantilla->precio,
                    'cambio' => 0,
                    'id_empleado' => $empleadoId,
                    'created_at' => $fechaCompra,
                    'updated_at' => $fechaCompra,
                ];
            }
        }

        DB::table('bonos_clientes')->insert($bonos);
    }
}
