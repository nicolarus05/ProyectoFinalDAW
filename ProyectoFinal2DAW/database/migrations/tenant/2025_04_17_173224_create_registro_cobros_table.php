<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{
        Schema::create('registro_cobros', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_cita')->constrained('citas')->onDelete('cascade');
            $table->foreignId('id_cliente')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('id_empleado')->nullable()->constrained('empleados')->onDelete('set null');

            $table->decimal('coste', 8, 2); 
            $table->decimal('descuento_porcentaje', 5, 2)->nullable(); 
            $table->decimal('descuento_euro', 8, 2)->nullable(); 
            $table->decimal('total_final', 8, 2); 
            $table->decimal('dinero_cliente', 8, 2); 
            $table->decimal('deuda', 10, 2)->default(0); 

            $table->enum('metodo_pago', ['efectivo', 'tarjeta'])->nullable(); 
            $table->decimal('cambio', 8, 2)->nullable();

            $table->timestamps();
        });

        // Solo ejecutar en MySQL/MariaDB (SQLite no soporta MODIFY COLUMN)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE registro_cobros MODIFY COLUMN metodo_pago ENUM('efectivo','tarjeta','bono','deuda') NOT NULL");
            DB::statement("UPDATE registro_cobros rc JOIN citas c ON rc.id_cita = c.id SET rc.id_cliente = c.id_cliente, rc.id_empleado = c.id_empleado WHERE rc.id_cliente IS NULL OR rc.id_empleado IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_cobros');
    }
};
