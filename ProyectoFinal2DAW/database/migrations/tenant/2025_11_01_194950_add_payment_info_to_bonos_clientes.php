<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bonos_clientes', function (Blueprint $table) {
            $table->enum('metodo_pago', ['efectivo', 'tarjeta'])->after('estado');
            $table->decimal('precio_pagado', 10, 2)->after('metodo_pago');
            $table->decimal('dinero_cliente', 10, 2)->nullable()->after('precio_pagado');
            $table->decimal('cambio', 10, 2)->default(0)->after('dinero_cliente');
            $table->foreignId('id_empleado')->nullable()->after('cambio')->constrained('empleados')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonos_clientes', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'precio_pagado', 'dinero_cliente', 'cambio', 'id_empleado']);
        });
    }
};
