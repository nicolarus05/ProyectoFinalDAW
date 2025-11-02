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
        Schema::create('bonos_plantilla', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre del bono (ej: "Bono Peluquería Básico")
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2); // Precio del bono
            $table->integer('duracion_dias')->nullable(); // Días de validez (NULL = sin límite)
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonos_plantilla');
    }
};
