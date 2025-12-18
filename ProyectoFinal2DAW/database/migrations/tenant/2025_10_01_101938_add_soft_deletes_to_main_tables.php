<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('clientes') && !Schema::hasColumn('clientes', 'deleted_at')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('empleados') && !Schema::hasColumn('empleados', 'deleted_at')) {
            Schema::table('empleados', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('citas') && !Schema::hasColumn('citas', 'deleted_at')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('servicios') && !Schema::hasColumn('servicios', 'deleted_at')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('deudas') && !Schema::hasColumn('deudas', 'deleted_at')) {
            Schema::table('deudas', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('productos') && !Schema::hasColumn('productos', 'deleted_at')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'deleted_at')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('empleados') && Schema::hasColumn('empleados', 'deleted_at')) {
            Schema::table('empleados', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('citas') && Schema::hasColumn('citas', 'deleted_at')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('servicios') && Schema::hasColumn('servicios', 'deleted_at')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('deudas') && Schema::hasColumn('deudas', 'deleted_at')) {
            Schema::table('deudas', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('productos') && Schema::hasColumn('productos', 'deleted_at')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};