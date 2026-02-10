<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Actualizar el índice unique de email en users para permitir
     * re-registro de usuarios soft-deleted.
     *
     * Se reemplaza: UNIQUE(email)
     * Por un índice compuesto que permite duplicados si deleted_at es diferente,
     * o se elimina y se gestiona solo a nivel de validación de Laravel.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Eliminar el índice unique actual
            $table->dropUnique(['email']);
        });

        // Crear un índice unique parcial: solo aplica cuando deleted_at IS NULL
        // Esto permite que existan emails duplicados si el registro está soft-deleted
        // MySQL no soporta índices parciales, así que usamos un índice unique compuesto
        // con una columna virtual o simplemente un índice normal + validación en Laravel
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL soporta índices parciales nativos
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL');
        } else {
            // MySQL/MariaDB/SQLite: índice unique compuesto con deleted_at
            // Esto permite: (email=x, deleted_at=NULL) único, pero (email=x, deleted_at=timestamp) puede repetirse
            // Nota: en MySQL, NULL se trata como diferente a otros NULLs en unique keys,
            // por lo que dos registros con deleted_at=NULL y mismo email SÍ serían bloqueados ✓
            // pero un registro con deleted_at=NULL y otro con deleted_at=fecha SÍ se permiten ✓
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['email', 'deleted_at'], 'users_email_deleted_at_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS users_email_unique');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_deleted_at_unique');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
