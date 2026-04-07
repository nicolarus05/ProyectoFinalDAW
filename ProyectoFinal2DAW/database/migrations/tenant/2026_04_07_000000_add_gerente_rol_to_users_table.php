<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN rol ENUM('admin', 'gerente', 'empleado', 'cliente') NOT NULL");
    }

    public function down(): void
    {
        // Convertir gerentes a empleados antes de eliminar el valor del ENUM
        DB::statement("UPDATE users SET rol = 'empleado' WHERE rol = 'gerente'");
        DB::statement("ALTER TABLE users MODIFY COLUMN rol ENUM('admin', 'empleado', 'cliente') NOT NULL");
    }
};
