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
        Schema::create('registro_cobro_bonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_cobro_id')->constrained('registro_cobros')->onDelete('cascade');
            $table->foreignId('bono_cliente_id')->constrained('bonos_clientes')->onDelete('cascade');
            $table->decimal('precio', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_cobro_bonos');
    }
};
