<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('registro_cobro_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_registro_cobro')->constrained('registro_cobros')->onDelete('cascade');
            $table->foreignId('id_producto')->constrained('productos')->onDelete('restrict');
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_cobro_productos');
    }
};
