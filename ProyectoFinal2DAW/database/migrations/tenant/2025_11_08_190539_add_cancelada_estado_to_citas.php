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
    public function up(): void
    {
        // Solo ejecutar en MySQL/MariaDB (SQLite no soporta MODIFY COLUMN ni ENUM)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE citas MODIFY COLUMN estado ENUM('pendiente', 'completada', 'cancelada') NOT NULL DEFAULT 'pendiente'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE citas MODIFY COLUMN estado ENUM('pendiente', 'completada') NOT NULL DEFAULT 'pendiente'");
        }
    }
};
