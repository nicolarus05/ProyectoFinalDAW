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
        Schema::create('movimientos_deuda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_deuda')->constrained('deudas')->onDelete('cascade');
            $table->foreignId('id_registro_cobro')->nullable()->constrained('registro_cobros')->onDelete('set null');
            $table->enum('tipo', ['cargo', 'abono']);
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago')->nullable();
            $table->text('nota')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->foreignId('usuario_registro_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_deuda');
    }
};
