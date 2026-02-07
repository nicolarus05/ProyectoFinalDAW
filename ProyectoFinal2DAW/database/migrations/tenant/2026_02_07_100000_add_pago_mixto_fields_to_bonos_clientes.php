<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Añade campos para desglosar pagos mixtos en bonos:
     * - pago_efectivo: cantidad pagada en efectivo
     * - pago_tarjeta: cantidad pagada con tarjeta
     */
    public function up(): void
    {
        Schema::table('bonos_clientes', function (Blueprint $table) {
            $table->decimal('pago_efectivo', 10, 2)->nullable()->after('precio_pagado');
            $table->decimal('pago_tarjeta', 10, 2)->nullable()->after('pago_efectivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bonos_clientes', function (Blueprint $table) {
            $table->dropColumn(['pago_efectivo', 'pago_tarjeta']);
        });
    }
};
