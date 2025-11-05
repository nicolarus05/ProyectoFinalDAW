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
            $table->decimal('pago_efectivo', 8, 2)->nullable()->after('dinero_cliente');
            $table->decimal('pago_tarjeta', 8, 2)->nullable()->after('pago_efectivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_cobros', function (Blueprint $table) {
            $table->dropColumn(['pago_efectivo', 'pago_tarjeta']);
        });
    }
};
