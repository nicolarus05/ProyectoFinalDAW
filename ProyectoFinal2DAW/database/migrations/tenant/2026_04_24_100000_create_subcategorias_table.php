<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subcategorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->enum('categoria', ['peluqueria', 'estetica']);
            $table->string('color', 7)->default('#6b7280'); // color hex
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategorias');
    }
};
