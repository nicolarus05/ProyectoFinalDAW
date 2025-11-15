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
            // Agregar campos para descuentos separados de servicios y productos
            $table->decimal('descuento_servicios_porcentaje', 5, 2)->default(0)->after('coste');
            $table->decimal('descuento_servicios_euro', 8, 2)->default(0)->after('descuento_servicios_porcentaje');
            $table->decimal('descuento_productos_porcentaje', 5, 2)->default(0)->after('descuento_servicios_euro');
            $table->decimal('descuento_productos_euro', 8, 2)->default(0)->after('descuento_productos_porcentaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_cobros', function (Blueprint $table) {
            $table->dropColumn([
                'descuento_servicios_porcentaje',
                'descuento_servicios_euro',
                'descuento_productos_porcentaje',
                'descuento_productos_euro'
            ]);
        });
    }
};
