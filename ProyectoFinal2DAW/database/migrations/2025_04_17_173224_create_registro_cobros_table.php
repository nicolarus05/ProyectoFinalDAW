<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{
        Schema::create('registro_cobros', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_cita')->constrained('citas')->onDelete('cascade');

            $table->decimal('coste', 8, 2); // Coste del servicio
            $table->decimal('descuento_porcentaje', 5, 2)->nullable(); // Descuento en porcentaje
            $table->decimal('descuento_euro', 8, 2)->nullable(); // Descuento en euros
            $table->decimal('total_final', 8, 2); // Total después de aplicar descuentos
            $table->decimal('dinero_cliente', 8, 2); // Dinero recibido por el cliente

            $table->enum('metodo_pago', ['efectivo', 'tarjeta']); // Método de pago
            $table->decimal('cambio', 8, 2)->nullable(); // Cambio devuelto al cliente

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
