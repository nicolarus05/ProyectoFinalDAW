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
        Schema::table('horario_trabajo', function (Blueprint $table) {
            // Añadir campo hora para bloques horarios específicos
            $table->time('hora')->nullable()->after('fecha');
            
            // Tipo de horario (normal o verano para julio-agosto)
            $table->enum('tipo_horario', ['normal', 'verano'])->default('normal')->after('disponible');
            
            // Hacer hora_inicio y hora_fin opcionales (para compatibilidad con nuevo sistema)
            $table->time('hora_inicio')->nullable()->change();
            $table->time('hora_fin')->nullable()->change();
            
            // Añadir índice compuesto para búsquedas rápidas
            $table->index(['id_empleado', 'fecha', 'hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horario_trabajo', function (Blueprint $table) {
            $table->dropIndex(['id_empleado', 'fecha', 'hora']);
            $table->dropColumn(['hora', 'tipo_horario']);
            
            // Restaurar hora_inicio y hora_fin como no nullables
            $table->time('hora_inicio')->nullable(false)->change();
            $table->time('hora_fin')->nullable(false)->change();
        });
    }
};
