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
            $table->decimal('total_bonos_vendidos', 8, 2)->default(0)->after('total_final');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_cobros', function (Blueprint $table) {
            $table->dropColumn('total_bonos_vendidos');
        });
    }
};
