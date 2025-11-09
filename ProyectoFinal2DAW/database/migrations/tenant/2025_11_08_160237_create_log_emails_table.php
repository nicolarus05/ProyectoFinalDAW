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
        Schema::create('log_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cita')->constrained('citas')->onDelete('cascade');
            $table->string('tipo_email'); // 'confirmacion', 'recordatorio', 'cancelacion'
            $table->string('email_destinatario');
            $table->string('estado'); // 'enviado', 'error'
            $table->text('mensaje_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_emails');
    }
};
