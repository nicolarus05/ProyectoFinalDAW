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
        Schema::create('horario_trabajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empleado')->constrained('empleados')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora')->nullable(); // Hora específica del bloque (para bloques individuales)
            $table->time('hora_inicio')->nullable(); // Hora de inicio de jornada (para rangos)
            $table->time('hora_fin')->nullable(); // Hora de fin de jornada (para rangos)
            $table->boolean('disponible')->default(true);
            $table->enum('tipo_horario', ['normal', 'verano'])->default('normal');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índices para optimizar búsquedas
            $table->index(['id_empleado', 'fecha']);
            $table->unique(['id_empleado', 'fecha', 'hora']); // Único para bloques individuales
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_trabajo');
    }
};
