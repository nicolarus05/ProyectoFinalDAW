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
        Schema::table('registro_entrada_salida', function (Blueprint $table) {
            $table->boolean('salida_fuera_horario')->default(false)->after('hora_salida');
            $table->integer('minutos_extra')->nullable()->after('salida_fuera_horario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_entrada_salida', function (Blueprint $table) {
            $table->dropColumn(['salida_fuera_horario', 'minutos_extra']);
        });
    }
};
