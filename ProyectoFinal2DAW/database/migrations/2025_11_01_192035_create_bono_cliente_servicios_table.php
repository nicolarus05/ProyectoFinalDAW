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
        Schema::create('bono_cliente_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bono_cliente_id')->constrained('bonos_clientes')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->integer('cantidad_total'); // Cantidad inicial del servicio en el bono
            $table->integer('cantidad_usada')->default(0); // Cantidad ya utilizada
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bono_cliente_servicios');
    }
};
