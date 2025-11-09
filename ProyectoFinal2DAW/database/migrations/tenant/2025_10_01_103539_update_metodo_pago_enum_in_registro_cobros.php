<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Cambiar el enum para incluir 'bono' y 'deuda'
        DB::statement("ALTER TABLE registro_cobros MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta', 'bono', 'deuda', 'mixto') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE registro_cobros MODIFY COLUMN metodo_pago ENUM('efectivo', 'tarjeta') NOT NULL");
    }
};