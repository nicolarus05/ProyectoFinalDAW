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
        Schema::create('bono_plantilla_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bono_plantilla_id')->constrained('bonos_plantilla')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->integer('cantidad'); // Cantidad de veces que incluye este servicio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bono_plantilla_servicios');
    }
};
