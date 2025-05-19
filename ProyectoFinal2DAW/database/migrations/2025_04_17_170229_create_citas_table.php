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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_hora'); // Fecha y hora de la cita
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada', 'completada']); // Estado de la cita
            $table->string('notas_adicionales')->nullable(); // Notas adicionales sobre la cita
            $table->foreignId('id_cliente')->constrained('clientes')->onDelete('cascade'); // Relación con la tabla clientes
            $table->foreignId('id_empleado')->constrained('empleados')->onDelete('cascade'); // Relación con la tabla empleados
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
