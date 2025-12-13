<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('deudas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('deudas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};