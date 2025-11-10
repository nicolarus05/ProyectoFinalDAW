<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega soft deletes a la tabla tenants para permitir
     * eliminación suave con período de gracia de 30 días.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->softDeletes()->after('data');
            $table->timestamp('backup_created_at')->nullable()->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['deleted_at', 'backup_created_at']);
        });
    }
};
