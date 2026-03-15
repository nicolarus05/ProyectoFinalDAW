<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite' || !Schema::hasTable('bonos_clientes')) {
            return;
        }

        // Agregar 'deuda' y 'mixto' al ENUM de metodo_pago en bonos_clientes
        DB::statement("ALTER TABLE bonos_clientes MODIFY COLUMN metodo_pago ENUM('efectivo','tarjeta','mixto','deuda') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' || !Schema::hasTable('bonos_clientes')) {
            return;
        }

        // Revertir a los valores originales
        DB::statement("ALTER TABLE bonos_clientes MODIFY COLUMN metodo_pago ENUM('efectivo','tarjeta') NOT NULL");
    }
};
