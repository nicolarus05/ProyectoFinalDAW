# Reorganización de Migraciones - Multi-Tenancy

## ✅ FASE 2 COMPLETADA

### Estructura Final

```
database/migrations/
├── 0001_01_01_000001_create_cache_table.php           [CENTRAL]
├── 0001_01_01_000002_create_jobs_table.php            [CENTRAL]
├── 2019_09_15_000010_create_tenants_table.php         [CENTRAL]
├── 2019_09_15_000020_create_domains_table.php         [CENTRAL]
└── tenant/
    ├── 0001_01_01_000000_create_users_table.php       [TENANT] ✨
    ├── 2025_04_17_170127_create_clientes_table.php    [TENANT]
    ├── 2025_04_17_170145_create_empleados_table.php   [TENANT]
    ├── 2025_04_17_170157_create_servicios_table.php   [TENANT]
    ├── 2025_04_17_170214_create_empleado_servicio_table.php [TENANT]
    ├── 2025_04_17_170229_create_citas_table.php       [TENANT]
    ├── 2025_04_17_170240_create_horario_trabajo_table.php [TENANT]
    ├── 2025_04_17_170257_create_registro_entrada_salida_table.php [TENANT]
    ├── 2025_04_17_173224_create_registro_cobros_table.php [TENANT]
    ├── 2025_05_19_161141_create_cita_servicio_table.php [TENANT]
    ├── 2025_10_01_101938_add_soft_deletes_to_main_tables.php [TENANT]
    ├── 2025_10_01_103539_update_metodo_pago_enum_in_registro_cobros.php [TENANT]
    ├── 2025_10_07_150956_create_productos_table.php   [TENANT]
    ├── 2025_10_07_164201_create_registro_cobro_productos_table.php [TENANT]
    ├── 2025_10_25_173629_create_deudas_table.php      [TENANT]
    ├── 2025_10_25_173647_create_movimientos_deuda_table.php [TENANT]
    ├── 2025_11_01_190911_add_categoria_to_productos_table.php [TENANT]
    ├── 2025_11_01_192028_create_bonos_plantilla_table.php [TENANT]
    ├── 2025_11_01_192030_create_bono_plantilla_servicios_table.php [TENANT]
    ├── 2025_11_01_192033_create_bonos_clientes_table.php [TENANT]
    ├── 2025_11_01_192035_create_bono_cliente_servicios_table.php [TENANT]
    ├── 2025_11_01_193213_modify_duracion_dias_nullable_in_bonos_plantilla.php [TENANT]
    ├── 2025_11_01_194950_add_payment_info_to_bonos_clientes.php [TENANT]
    ├── 2025_11_02_115308_update_tipo_and_especializacion_to_categoria.php [TENANT]
    ├── 2025_11_05_095503_create_bono_uso_detalle_table.php [TENANT]
    ├── 2025_11_05_101752_add_pago_mixto_to_registro_cobros.php [TENANT]
    ├── 2025_11_08_112422_add_duracion_minutos_to_citas_table.php [TENANT]
    ├── 2025_11_08_160237_create_log_emails_table.php [TENANT]
    ├── 2025_11_08_183804_make_id_cita_nullable_in_registro_cobros_table.php [TENANT]
    ├── 2025_11_08_183950_add_mixto_to_metodo_pago_in_registro_cobros.php [TENANT]
    ├── 2025_11_08_190122_update_citas_estado_enum.php [TENANT]
    └── 2025_11_08_190539_add_cancelada_estado_to_citas.php [TENANT]
```

### Resumen de Cambios

**Total de migraciones:**
- **Centrales**: 4 migraciones
- **Tenant**: 32 migraciones

### Migraciones Centrales (Base de datos `central`)

Estas migraciones se ejecutan **UNA SOLA VEZ** en la base de datos central:

1. **cache** - Tabla de caché compartida (opcional)
2. **jobs** - Tabla de trabajos en cola compartida (opcional)
3. **tenants** - Tabla que almacena información de cada salón
4. **domains** - Tabla que mapea dominios a tenants

### Migraciones Tenant (Base de datos por salón)

Estas migraciones se ejecutan **POR CADA TENANT** en su propia base de datos:

1. **users** (incluye: users, password_reset_tokens, sessions) ✨
2. **clientes** - Clientes del salón
3. **empleados** - Empleados del salón
4. **servicios** - Servicios ofrecidos
5. **citas** - Sistema de citas
6. **productos** - Inventario de productos
7. **cobros** - Registros de cobros
8. **bonos** - Sistema de bonos
9. **deudas** - Gestión de deudas
10. **horarios** - Horarios de trabajo
11. **asistencia** - Control de entrada/salida
12. Y todas las modificaciones/alteraciones posteriores

### ✨ Nota Importante: Tabla Users

La tabla `users` ahora está en el ámbito TENANT, lo que significa:

- ✅ Cada salón tiene sus propios usuarios (admin, empleados, clientes)
- ✅ Los usuarios de un salón NO pueden ver/acceder a otros salones
- ✅ El login se hace en el subdominio del salón (ej: lola.misalon.com)
- ✅ La sesión se guarda en la BD del tenant específico

### Tabla Sessions Incluida

La migración `0001_01_01_000000_create_users_table.php` ya incluye la tabla `sessions`, por lo que está lista para usar `SESSION_DRIVER=database` en cada tenant.

### Comandos de Migración

```bash
# Migrar la base de datos CENTRAL (solo tenants y domains)
php artisan migrate

# Migrar TODOS los tenants
php artisan tenants:migrate

# Migrar un tenant específico
php artisan tenants:migrate --tenants=salonlola

# Rollback en un tenant específico
php artisan tenants:rollback --tenants=salonlola
```

### Verificación

Para verificar que la reorganización es correcta:

```bash
# Ver migraciones centrales (4 archivos)
ls -1 database/migrations/*.php | wc -l

# Ver migraciones tenant (32 archivos)
ls -1 database/migrations/tenant/*.php | wc -l
```

### ⚠️ Corrección al Plan Original

**Cambio respecto al plan**: En el `MULTI_TENANCY_IMPLEMENTATION_PLAN.md` se indica que `password_reset_tokens` debería estar en CENTRAL, pero esto es **arquitectónicamente incorrecto**.

**Decisión tomada**: `password_reset_tokens` está en **TENANT** (dentro de la migración de users) porque:
- ✅ Cada tenant gestiona sus propios usuarios
- ✅ Los tokens de reseteo pertenecen a usuarios específicos del tenant
- ✅ Aislamiento completo de datos de autenticación
- ✅ Sin riesgo de conflictos entre tokens de diferentes tenants

**Resultado final CORRECTO:**
- **CENTRAL**: cache, jobs, tenants, domains (4 tablas)
- **TENANT**: users, password_reset_tokens, sessions, y todas las tablas de negocio (32 migraciones)

### Verificación de FASE 2

```bash
# ✅ Verificado: 4 migraciones centrales
ls -1 database/migrations/*.php
# 0001_01_01_000001_create_cache_table.php
# 0001_01_01_000002_create_jobs_table.php
# 2019_09_15_000010_create_tenants_table.php
# 2019_09_15_000020_create_domains_table.php

# ✅ Verificado: 32 migraciones tenant
ls -1 database/migrations/tenant/*.php | wc -l
# 32

# ✅ Verificado: password_reset_tokens en tenant
grep "password_reset_tokens" database/migrations/tenant/0001_01_01_000000_create_users_table.php
# Schema::create('password_reset_tokens', ...)

# ✅ Verificado: sessions en tenant
grep "sessions" database/migrations/tenant/0001_01_01_000000_create_users_table.php
# Schema::create('sessions', ...)
```

### Próximos Pasos

Con la reorganización completada, podemos pasar a:
- **FASE 3**: Configuración de Rutas y Middleware
- **FASE 4**: Configuración de Sesiones y Autenticación
- **FASE 5**: Flujo de Registro de Tenant

---

**Fecha de completación**: 9 de noviembre de 2025
**Estado**: ✅ COMPLETADO Y VERIFICADO
