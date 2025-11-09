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
        Schema::create('registro_entrada_salida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empleado')->constrained('empleados')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_entrada')->nullable(); // Hora de entrada del empleado
            $table->time('hora_salida')->nullable(); // Hora de salida del empleado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_entrada_salida');
    }
};
