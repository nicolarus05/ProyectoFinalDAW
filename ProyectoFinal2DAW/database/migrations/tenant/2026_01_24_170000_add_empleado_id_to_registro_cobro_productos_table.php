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
        if (!Schema::hasTable('registro_cobro_productos') || Schema::hasColumn('registro_cobro_productos', 'empleado_id')) {
            return;
        }

        Schema::table('registro_cobro_productos', function (Blueprint $table) {
            // Añadir columna empleado_id después de id_producto
            $table->unsignedBigInteger('empleado_id')->nullable()->after('id_producto');
            
            // Añadir foreign key
            $table->foreign('empleado_id')
                  ->references('id')
                  ->on('empleados')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('registro_cobro_productos') || !Schema::hasColumn('registro_cobro_productos', 'empleado_id')) {
            return;
        }

        Schema::table('registro_cobro_productos', function (Blueprint $table) {
            $table->dropForeign(['empleado_id']);
            $table->dropColumn('empleado_id');
        });
    }
};
