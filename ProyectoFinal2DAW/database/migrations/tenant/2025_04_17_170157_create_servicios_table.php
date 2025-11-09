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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre del servicio
            $table->integer('tiempo_estimado'); // Duración del servicio en minutos
            $table->decimal('precio', 8, 2); // Precio del servicio
            $table->string('tipo'); // Tipo de servicio (ej. "peluquería", "estética")
            $table->boolean('activo')->default(true); // Estado del servicio (activo/inactivo)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
