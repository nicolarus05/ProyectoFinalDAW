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
        Schema::create('registro_cobros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cita')->constrained('citas')->onDelete('cascade');
            $table->decimal('coste', 8, 2); // Coste del servicio
            $table->decimal('descuento_porcentaje', 5, 2)->nullable(); // Descuento aplicado en porcentaje
            $table->decimal('descuento_euro', 8, 2)->nullable(); // Descuento aplicado en euro
            $table->decimal('total_final', 8, 2); // Total a pagar después de aplicar descuentos
            $table->enum('metodo_pago', ['efectivo', 'tarjeta']); // Método de pago utilizado
            $table->decimal('cambio', 8, 2)->nullable(); // Cambio devuelto al cliente (si aplica)
            $table->foreignId('id_empelado')->constrained('empleados')->cascadeOnDelete();
            $table->foreignId('id_servicio')->constrained('servicios')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_cobros');
    }
};
