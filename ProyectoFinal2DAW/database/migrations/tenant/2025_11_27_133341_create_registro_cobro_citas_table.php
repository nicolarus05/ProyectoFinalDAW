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
        Schema::create('registro_cobro_citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_cobro_id')->constrained('registro_cobros')->onDelete('cascade');
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_cobro_citas');
    }
};
