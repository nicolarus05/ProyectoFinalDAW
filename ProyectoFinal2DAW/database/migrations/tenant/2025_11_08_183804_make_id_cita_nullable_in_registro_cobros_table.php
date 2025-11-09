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
        Schema::table('registro_cobros', function (Blueprint $table) {
            // Eliminar la restricción de foreign key existente
            $table->dropForeign(['id_cita']);
            
            // Modificar la columna para que sea nullable
            $table->unsignedBigInteger('id_cita')->nullable()->change();
            
            // Volver a agregar la foreign key
            $table->foreign('id_cita')->references('id')->on('citas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_cobros', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['id_cita']);
            
            // Revertir la columna a NOT NULL
            $table->unsignedBigInteger('id_cita')->nullable(false)->change();
            
            // Volver a agregar la foreign key
            $table->foreign('id_cita')->references('id')->on('citas')->onDelete('cascade');
        });
    }
};
