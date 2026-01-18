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
            $table->boolean('contabilizado')->default(true)->after('deuda');
            $table->index('contabilizado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_cobros', function (Blueprint $table) {
            $table->dropIndex(['contabilizado']);
            $table->dropColumn('contabilizado');
        });
    }
};
