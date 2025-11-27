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
        Schema::table('citas', function (Blueprint $table) {
            $table->unsignedBigInteger('grupo_cita_id')->nullable()->after('id');
            $table->integer('orden_servicio')->default(1)->after('grupo_cita_id');
            
            $table->index('grupo_cita_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex(['grupo_cita_id']);
            $table->dropColumn(['grupo_cita_id', 'orden_servicio']);
        });
    }
};
