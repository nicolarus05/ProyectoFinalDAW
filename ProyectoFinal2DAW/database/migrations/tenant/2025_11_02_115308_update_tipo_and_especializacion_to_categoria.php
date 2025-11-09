<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar servicios: cambiar tipo a enum si no lo es ya
        Schema::table('servicios', function (Blueprint $table) {
            // Renombrar 'tipo' a 'categoria' y hacerlo enum
            $table->enum('categoria', ['peluqueria', 'estetica'])->default('peluqueria')->after('precio');
        });
        
        // Copiar datos de 'tipo' a 'categoria' y luego eliminar 'tipo'
        DB::statement("UPDATE servicios SET categoria = CASE 
            WHEN LOWER(tipo) LIKE '%peluquer%' THEN 'peluqueria'
            WHEN LOWER(tipo) LIKE '%est%tica%' OR LOWER(tipo) LIKE '%estetica%' THEN 'estetica'
            ELSE 'peluqueria'
        END");
        
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        // Actualizar empleados: cambiar especializacion a categoria enum
        Schema::table('empleados', function (Blueprint $table) {
            $table->enum('categoria', ['peluqueria', 'estetica'])->default('peluqueria')->after('id_user');
        });
        
        // Copiar datos de 'especializacion' a 'categoria'
        DB::statement("UPDATE empleados SET categoria = CASE 
            WHEN LOWER(especializacion) LIKE '%peluquer%' THEN 'peluqueria'
            WHEN LOWER(especializacion) LIKE '%est%tica%' OR LOWER(especializacion) LIKE '%estetica%' THEN 'estetica'
            ELSE 'peluqueria'
        END");
        
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('especializacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir servicios
        Schema::table('servicios', function (Blueprint $table) {
            $table->string('tipo')->after('precio');
        });
        
        DB::statement("UPDATE servicios SET tipo = categoria");
        
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });

        // Revertir empleados
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('especializacion')->after('id_user');
        });
        
        DB::statement("UPDATE empleados SET especializacion = categoria");
        
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
