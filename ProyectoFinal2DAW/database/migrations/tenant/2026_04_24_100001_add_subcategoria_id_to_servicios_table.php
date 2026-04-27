<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->foreignId('subcategoria_id')
                  ->nullable()
                  ->after('categoria')
                  ->constrained('subcategorias')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropForeign(['subcategoria_id']);
            $table->dropColumn('subcategoria_id');
        });
    }
};
