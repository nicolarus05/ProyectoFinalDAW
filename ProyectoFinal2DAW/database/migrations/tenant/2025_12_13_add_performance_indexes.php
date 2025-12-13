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
        // Índices para users
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'idx_users_email')) {
                $table->index('email', 'idx_users_email');
            }
            if (!$this->hasIndex('users', 'idx_users_rol')) {
                $table->index('rol', 'idx_users_rol');
            }
            if (!$this->hasIndex('users', 'idx_users_rol_email')) {
                $table->index(['rol', 'email'], 'idx_users_rol_email');
            }
        });

        // Índices para clientes
        Schema::table('clientes', function (Blueprint $table) {
            if (!$this->hasIndex('clientes', 'idx_clientes_id_user')) {
                $table->index('id_user', 'idx_clientes_id_user');
            }
            if (!$this->hasIndex('clientes', 'idx_clientes_fecha_registro')) {
                $table->index('fecha_registro', 'idx_clientes_fecha_registro');
            }
        });

        // Índices para empleados
        Schema::table('empleados', function (Blueprint $table) {
            if (!$this->hasIndex('empleados', 'idx_empleados_id_user')) {
                $table->index('id_user', 'idx_empleados_id_user');
            }
            if (!$this->hasIndex('empleados', 'idx_empleados_categoria')) {
                $table->index('categoria', 'idx_empleados_categoria');
            }
        });

        // Índices para citas (MÁS IMPORTANTE)
        Schema::table('citas', function (Blueprint $table) {
            if (!$this->hasIndex('citas', 'idx_citas_fecha_hora')) {
                $table->index('fecha_hora', 'idx_citas_fecha_hora');
            }
            if (!$this->hasIndex('citas', 'idx_citas_estado')) {
                $table->index('estado', 'idx_citas_estado');
            }
            if (!$this->hasIndex('citas', 'idx_citas_id_cliente')) {
                $table->index('id_cliente', 'idx_citas_id_cliente');
            }
            if (!$this->hasIndex('citas', 'idx_citas_id_empleado')) {
                $table->index('id_empleado', 'idx_citas_id_empleado');
            }
            if (!$this->hasIndex('citas', 'idx_citas_fecha_estado')) {
                $table->index(['fecha_hora', 'estado'], 'idx_citas_fecha_estado');
            }
            if (!$this->hasIndex('citas', 'idx_citas_empleado_fecha_estado')) {
                $table->index(['id_empleado', 'fecha_hora', 'estado'], 'idx_citas_empleado_fecha_estado');
            }
            if (!$this->hasIndex('citas', 'idx_citas_grupo_cita_id')) {
                $table->index('grupo_cita_id', 'idx_citas_grupo_cita_id');
            }
        });

        // Índices para horario_trabajo
        Schema::table('horario_trabajo', function (Blueprint $table) {
            if (!$this->hasIndex('horario_trabajo', 'idx_horario_trabajo_disponible')) {
                $table->index('disponible', 'idx_horario_trabajo_disponible');
            }
            if (!$this->hasIndex('horario_trabajo', 'idx_horario_empleado_fecha_disponible')) {
                $table->index(['id_empleado', 'fecha', 'disponible'], 'idx_horario_empleado_fecha_disponible');
            }
        });

        // Índices para registro_cobros
        Schema::table('registro_cobros', function (Blueprint $table) {
            if (!$this->hasIndex('registro_cobros', 'idx_registro_cobros_id_cita')) {
                $table->index('id_cita', 'idx_registro_cobros_id_cita');
            }
            if (!$this->hasIndex('registro_cobros', 'idx_registro_cobros_id_cliente')) {
                $table->index('id_cliente', 'idx_registro_cobros_id_cliente');
            }
            if (!$this->hasIndex('registro_cobros', 'idx_registro_cobros_id_empleado')) {
                $table->index('id_empleado', 'idx_registro_cobros_id_empleado');
            }
            if (!$this->hasIndex('registro_cobros', 'idx_registro_cobros_metodo_pago')) {
                $table->index('metodo_pago', 'idx_registro_cobros_metodo_pago');
            }
            if (!$this->hasIndex('registro_cobros', 'idx_registro_cobros_created_at')) {
                $table->index('created_at', 'idx_registro_cobros_created_at');
            }
        });

        // Índices para deudas
        Schema::table('deudas', function (Blueprint $table) {
            if (!$this->hasIndex('deudas', 'idx_deudas_id_cliente')) {
                $table->index('id_cliente', 'idx_deudas_id_cliente');
            }
            if (!$this->hasIndex('deudas', 'idx_deudas_saldo_pendiente')) {
                $table->index('saldo_pendiente', 'idx_deudas_saldo_pendiente');
            }
        });

        // Índices para movimientos_deuda
        Schema::table('movimientos_deuda', function (Blueprint $table) {
            if (!$this->hasIndex('movimientos_deuda', 'idx_movimientos_deuda_id_deuda')) {
                $table->index('id_deuda', 'idx_movimientos_deuda_id_deuda');
            }
            if (!$this->hasIndex('movimientos_deuda', 'idx_movimientos_deuda_tipo')) {
                $table->index('tipo', 'idx_movimientos_deuda_tipo');
            }
            if (!$this->hasIndex('movimientos_deuda', 'idx_movimientos_deuda_created_at')) {
                $table->index('created_at', 'idx_movimientos_deuda_created_at');
            }
        });

        // Índices para servicios
        Schema::table('servicios', function (Blueprint $table) {
            if (!$this->hasIndex('servicios', 'idx_servicios_tipo')) {
                $table->index('tipo', 'idx_servicios_tipo');
            }
            if (!$this->hasIndex('servicios', 'idx_servicios_activo')) {
                $table->index('activo', 'idx_servicios_activo');
            }
            if (!$this->hasIndex('servicios', 'idx_servicios_tipo_activo')) {
                $table->index(['tipo', 'activo'], 'idx_servicios_tipo_activo');
            }
        });

        // Índices para productos
        Schema::table('productos', function (Blueprint $table) {
            if (!$this->hasIndex('productos', 'idx_productos_categoria')) {
                $table->index('categoria', 'idx_productos_categoria');
            }
            if (!$this->hasIndex('productos', 'idx_productos_activo')) {
                $table->index('activo', 'idx_productos_activo');
            }
        });

        // Índices para bonos_clientes
        Schema::table('bonos_clientes', function (Blueprint $table) {
            if (!$this->hasIndex('bonos_clientes', 'idx_bonos_clientes_cliente_id')) {
                $table->index('cliente_id', 'idx_bonos_clientes_cliente_id');
            }
            if (!$this->hasIndex('bonos_clientes', 'idx_bonos_clientes_estado')) {
                $table->index('estado', 'idx_bonos_clientes_estado');
            }
            if (!$this->hasIndex('bonos_clientes', 'idx_bonos_clientes_fecha_expiracion')) {
                $table->index('fecha_expiracion', 'idx_bonos_clientes_fecha_expiracion');
            }
            if (!$this->hasIndex('bonos_clientes', 'idx_bonos_clientes_cliente_estado')) {
                $table->index(['cliente_id', 'estado'], 'idx_bonos_clientes_cliente_estado');
            }
        });

        // Índices para registro_entrada_salida
        Schema::table('registro_entrada_salida', function (Blueprint $table) {
            if (!$this->hasIndex('registro_entrada_salida', 'idx_registro_entrada_id_empleado')) {
                $table->index('id_empleado', 'idx_registro_entrada_id_empleado');
            }
            if (!$this->hasIndex('registro_entrada_salida', 'idx_registro_entrada_fecha')) {
                $table->index('fecha', 'idx_registro_entrada_fecha');
            }
            if (!$this->hasIndex('registro_entrada_salida', 'idx_registro_entrada_empleado_fecha')) {
                $table->index(['id_empleado', 'fecha'], 'idx_registro_entrada_empleado_fecha');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de users
        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'idx_users_email')) {
                $table->dropIndex('idx_users_email');
            }
            if ($this->hasIndex('users', 'idx_users_rol')) {
                $table->dropIndex('idx_users_rol');
            }
            if ($this->hasIndex('users', 'idx_users_rol_email')) {
                $table->dropIndex('idx_users_rol_email');
            }
        });

        // Eliminar índices de clientes
        Schema::table('clientes', function (Blueprint $table) {
            if ($this->hasIndex('clientes', 'idx_clientes_id_user')) {
                $table->dropIndex('idx_clientes_id_user');
            }
            if ($this->hasIndex('clientes', 'idx_clientes_fecha_registro')) {
                $table->dropIndex('idx_clientes_fecha_registro');
            }
        });

        // Eliminar índices de empleados
        Schema::table('empleados', function (Blueprint $table) {
            if ($this->hasIndex('empleados', 'idx_empleados_id_user')) {
                $table->dropIndex('idx_empleados_id_user');
            }
            if ($this->hasIndex('empleados', 'idx_empleados_categoria')) {
                $table->dropIndex('idx_empleados_categoria');
            }
        });

        // Eliminar índices de citas
        Schema::table('citas', function (Blueprint $table) {
            if ($this->hasIndex('citas', 'idx_citas_fecha_hora')) {
                $table->dropIndex('idx_citas_fecha_hora');
            }
            if ($this->hasIndex('citas', 'idx_citas_estado')) {
                $table->dropIndex('idx_citas_estado');
            }
            if ($this->hasIndex('citas', 'idx_citas_id_cliente')) {
                $table->dropIndex('idx_citas_id_cliente');
            }
            if ($this->hasIndex('citas', 'idx_citas_id_empleado')) {
                $table->dropIndex('idx_citas_id_empleado');
            }
            if ($this->hasIndex('citas', 'idx_citas_fecha_estado')) {
                $table->dropIndex('idx_citas_fecha_estado');
            }
            if ($this->hasIndex('citas', 'idx_citas_empleado_fecha_estado')) {
                $table->dropIndex('idx_citas_empleado_fecha_estado');
            }
            if ($this->hasIndex('citas', 'idx_citas_grupo_cita_id')) {
                $table->dropIndex('idx_citas_grupo_cita_id');
            }
        });

        // Eliminar índices de horario_trabajo
        Schema::table('horario_trabajo', function (Blueprint $table) {
            if ($this->hasIndex('horario_trabajo', 'idx_horario_trabajo_disponible')) {
                $table->dropIndex('idx_horario_trabajo_disponible');
            }
            if ($this->hasIndex('horario_trabajo', 'idx_horario_empleado_fecha_disponible')) {
                $table->dropIndex('idx_horario_empleado_fecha_disponible');
            }
        });

        // Eliminar índices de registro_cobros
        Schema::table('registro_cobros', function (Blueprint $table) {
            if ($this->hasIndex('registro_cobros', 'idx_registro_cobros_id_cita')) {
                $table->dropIndex('idx_registro_cobros_id_cita');
            }
            if ($this->hasIndex('registro_cobros', 'idx_registro_cobros_id_cliente')) {
                $table->dropIndex('idx_registro_cobros_id_cliente');
            }
            if ($this->hasIndex('registro_cobros', 'idx_registro_cobros_id_empleado')) {
                $table->dropIndex('idx_registro_cobros_id_empleado');
            }
            if ($this->hasIndex('registro_cobros', 'idx_registro_cobros_metodo_pago')) {
                $table->dropIndex('idx_registro_cobros_metodo_pago');
            }
            if ($this->hasIndex('registro_cobros', 'idx_registro_cobros_created_at')) {
                $table->dropIndex('idx_registro_cobros_created_at');
            }
        });

        // Eliminar índices de deudas
        Schema::table('deudas', function (Blueprint $table) {
            if ($this->hasIndex('deudas', 'idx_deudas_id_cliente')) {
                $table->dropIndex('idx_deudas_id_cliente');
            }
            if ($this->hasIndex('deudas', 'idx_deudas_saldo_pendiente')) {
                $table->dropIndex('idx_deudas_saldo_pendiente');
            }
        });

        // Eliminar índices de movimientos_deuda
        Schema::table('movimientos_deuda', function (Blueprint $table) {
            if ($this->hasIndex('movimientos_deuda', 'idx_movimientos_deuda_id_deuda')) {
                $table->dropIndex('idx_movimientos_deuda_id_deuda');
            }
            if ($this->hasIndex('movimientos_deuda', 'idx_movimientos_deuda_tipo')) {
                $table->dropIndex('idx_movimientos_deuda_tipo');
            }
            if ($this->hasIndex('movimientos_deuda', 'idx_movimientos_deuda_created_at')) {
                $table->dropIndex('idx_movimientos_deuda_created_at');
            }
        });

        // Eliminar índices de servicios
        Schema::table('servicios', function (Blueprint $table) {
            if ($this->hasIndex('servicios', 'idx_servicios_tipo')) {
                $table->dropIndex('idx_servicios_tipo');
            }
            if ($this->hasIndex('servicios', 'idx_servicios_activo')) {
                $table->dropIndex('idx_servicios_activo');
            }
            if ($this->hasIndex('servicios', 'idx_servicios_tipo_activo')) {
                $table->dropIndex('idx_servicios_tipo_activo');
            }
        });

        // Eliminar índices de productos
        Schema::table('productos', function (Blueprint $table) {
            if ($this->hasIndex('productos', 'idx_productos_categoria')) {
                $table->dropIndex('idx_productos_categoria');
            }
            if ($this->hasIndex('productos', 'idx_productos_activo')) {
                $table->dropIndex('idx_productos_activo');
            }
        });

        // Eliminar índices de bonos_clientes
        Schema::table('bonos_clientes', function (Blueprint $table) {
            if ($this->hasIndex('bonos_clientes', 'idx_bonos_clientes_cliente_id')) {
                $table->dropIndex('idx_bonos_clientes_cliente_id');
            }
            if ($this->hasIndex('bonos_clientes', 'idx_bonos_clientes_estado')) {
                $table->dropIndex('idx_bonos_clientes_estado');
            }
            if ($this->hasIndex('bonos_clientes', 'idx_bonos_clientes_fecha_expiracion')) {
                $table->dropIndex('idx_bonos_clientes_fecha_expiracion');
            }
            if ($this->hasIndex('bonos_clientes', 'idx_bonos_clientes_cliente_estado')) {
                $table->dropIndex('idx_bonos_clientes_cliente_estado');
            }
        });

        // Eliminar índices de registro_entrada_salida
        Schema::table('registro_entrada_salida', function (Blueprint $table) {
            if ($this->hasIndex('registro_entrada_salida', 'idx_registro_entrada_id_empleado')) {
                $table->dropIndex('idx_registro_entrada_id_empleado');
            }
            if ($this->hasIndex('registro_entrada_salida', 'idx_registro_entrada_fecha')) {
                $table->dropIndex('idx_registro_entrada_fecha');
            }
            if ($this->hasIndex('registro_entrada_salida', 'idx_registro_entrada_empleado_fecha')) {
                $table->dropIndex('idx_registro_entrada_empleado_fecha');
            }
        });
    }

    /**
     * Helper method to check if an index exists
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};
