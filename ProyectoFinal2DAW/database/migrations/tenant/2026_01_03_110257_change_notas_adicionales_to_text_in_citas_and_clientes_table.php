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
            $table->text('notas_adicionales')->nullable()->change();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->text('notas_adicionales')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('notas_adicionales')->nullable()->change();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('notas_adicionales')->nullable()->change();
        });
    }
};
