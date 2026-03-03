<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade registro_cobro_id a bono_uso_detalle para poder vincular directamente
     * los usos de bono con el cobro que los generó, eliminando la necesidad
     * de matching temporal frágil en destroy().
     */
    public function up(): void
    {
        Schema::table('bono_uso_detalle', function (Blueprint $table) {
            $table->unsignedBigInteger('registro_cobro_id')->nullable()->after('bono_cliente_id');
            $table->index('registro_cobro_id');
        });
    }

    public function down(): void
    {
        Schema::table('bono_uso_detalle', function (Blueprint $table) {
            $table->dropIndex(['registro_cobro_id']);
            $table->dropColumn('registro_cobro_id');
        });
    }
};
