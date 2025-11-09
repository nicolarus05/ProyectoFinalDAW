# Plan de Implementación Multi-Tenancy para Salón Lola Hernández

## Información del Proyecto
- **Laravel Version**: 12.8.1 (v12.0 en composer.json)
- **PHP Version**: ^8.2
- **Base de Datos**: MySQL (via Laravel Sail)
- **Dominio Central**: salonlolahernandez.ddns.net (puerto 90 en desarrollo)
- **Patrón Multi-Tenancy**: Base de datos separada por tenant + Subdominios

## Objetivo
Transformar la aplicación monolítica actual en un SaaS multi-tenant donde cada salón de belleza tendrá:
- Su propia base de datos aislada
- Su propio subdominio: `{salon}.misalon.com` (o `{salon}.salonlolahernandez.ddns.net` en dev)
- Datos completamente separados: usuarios, clientes, citas, productos, etc.

## Fases de Implementación

### FASE 1: Instalación y Configuración Base ✅
1. Instalar stancl/tenancy: `composer require stancl/tenancy`
2. Ejecutar: `php artisan tenancy:install`
3. Registrar TenancyServiceProvider (automático con tenancy:install)
4. Crear modelo personalizado `app/Models/Tenant.php` con HasDatabase y HasDomains
5. Configurar `config/tenancy.php`

### FASE 2: Reorganización de Migraciones ✅
1. Crear carpeta `database/migrations/tenant/`
2. Mover migraciones de la aplicación (users, clientes, empleados, servicios, citas, productos, etc.) a tenant/
3. Mantener en `database/migrations/`: tenants, domains, cache (central), jobs (central)
4. **IMPORTANTE**: users, password_reset_tokens y sessions deben estar en TENANT (cada salón gestiona sus propios usuarios)
5. Verificar que la migración de users incluye las 3 tablas: users, password_reset_tokens, sessions

### FASE 3: Configuración de Rutas y Middleware ✅ **COMPLETADA**
1. ✅ Mantener `routes/web.php` para rutas centrales (landing, registro tenant)
2. ✅ Crear `routes/tenant.php` para rutas de aplicación (dashboard, clientes, citas, etc.) - 135+ rutas
3. ✅ Configurar middleware en `bootstrap/app.php`:
   - Rutas centrales: dominio principal sin tenant middleware
   - Rutas tenant: InitializeTenancyBySubdomain::class + PreventAccessFromCentralDomains::class
4. ✅ Verificar que StartSession está en grupo 'web'
5. ✅ Configurar dominios centrales en config/tenancy.php

### FASE 4: Configuración de Sesiones y Autenticación ✅ **COMPLETADA**
1. ✅ SESSION_DRIVER=database ya configurado (recomendado para multi-tenancy)
2. ✅ SESSION_DOMAIN=.salonlolahernandez.ddns.net agregado al .env (wildcard para subdominios)
3. ✅ Tabla `sessions` existe en migraciones tenant (dentro de 0001_01_01_000000_create_users_table.php)
4. ✅ DatabaseTenancyBootstrapper activo - sesiones se guardan automáticamente en BD del tenant
5. ✅ Configuración verificada y cachés limpiados

### FASE 5: Flujo de Registro de Tenant (Creación de Salones) 📝
1. Crear controlador `TenantRegistrationController`
2. Implementar formulario de registro en dominio central
3. Lógica de registro:
   ```php
   - Validar datos (nombre salón, email admin, etc.)
   - Crear Tenant con slug único
   - $tenant->domains()->create(['domain' => "{slug}.misalon.com"])
   - $tenant->save() (crea BD automáticamente con evento)
   - tenancy()->initialize($tenant)
   - Ejecutar seed inicial (crear usuario admin en tenant)
   - Redirigir a https://{slug}.misalon.com
   ```
4. Configurar eventos de tenancy para:
   - Crear BD automáticamente (TenantCreated event)
   - Ejecutar migraciones tenant automáticamente
   - Seed inicial de datos tenant

### FASE 6: Storage y Archivos 📝
1. Configurar filesystem tenant-aware:
   - Opción A: `storage/app/tenants/{tenant_id}/...`
   - Opción B: S3 con prefijo `tenant_{id}/`
2. Actualizar código de subida de archivos para usar ruta tenant
3. Probar subida de fotos de perfil, productos, etc.

### FASE 7: Jobs, Cache y Queues 📝
1. Configurar queue workers tenant-aware
2. Si usa Redis, configurar RedisTenancyBootstrapper
3. Probar envío de emails (citas, confirmaciones) en contexto tenant
4. Asegurar que jobs mantienen contexto de tenant

### FASE 8: Scripts de Backup y Restauración 📝
1. Crear script bash `backup-tenants.sh`:
   ```bash
   - Iterar sobre todos los tenants en BD central
   - mysqldump de cada BD tenant
   - Nombre: {tenant_id}_{timestamp}.sql
   - Comprimir con gzip
   ```
2. Crear script `restore-tenant.sh`:
   ```bash
   - Restaurar dump específico
   - Recrear tenant en BD central si es necesario
   ```
3. Documentar proceso de backup/restore

### FASE 9: Tests Automáticos (QA) 📝
1. Test de creación de tenants:
   - Crear tenant "lola" y "belen"
   - Verificar que se crean BDs `tenant*` 
2. Test de aislamiento de datos:
   - Crear usuario y datos en "lola"
   - Crear usuario y datos en "belen"
   - Verificar que datos no se cruzan
3. Test de autenticación:
   - Login en lola.misalon.com
   - Verificar sesión en BD lola
   - Login en belen.misalon.com
   - Verificar sesión en BD belen
4. Test de migraciones tenant:
   - `php artisan tenants:migrate` aplica a todas las BDs tenant

### FASE 10: Despliegue en Render 📝
1. Configurar variables de entorno en Render:
   ```
   APP_KEY=...
   DB_CONNECTION=mysql
   DB_HOST=...
   DB_PORT=3306
   DB_DATABASE=central
   DB_USERNAME=...
   DB_PASSWORD=...
   SESSION_DRIVER=database
   SESSION_DOMAIN=.misalon.com
   TENANCY_CENTRAL_DOMAINS=misalon.com
   ```
2. Build Command: `composer install && php artisan migrate --force`
3. Start Command: `php artisan serve --host=0.0.0.0 --port=80`
4. Deploy Hook: `php artisan tenants:migrate --force`
5. Configurar dominios en Render:
   - Dominio principal: `misalon.com`
   - Wildcard: `*.misalon.com` (requiere plan paid)
6. Configurar DNS:
   - A record: `misalon.com` → IP de Render
   - CNAME record: `*.misalon.com` → `misalon.com`

### FASE 11: Seguridad y Operaciones 📝
1. Implementar eliminación segura de tenant:
   - Política: dump automático antes de eliminar
   - Confirmación doble
   - Soft delete en tabla tenants (delay de 30 días)
   - Script de purga permanente
2. Documentar límites:
   - Nombres de BD: max 64 caracteres
   - Slug de tenant: alfanumérico, guiones, 3-20 chars
3. Comandos artisan:
   - `php artisan tenant:create {slug} {domain}`
   - `php artisan tenant:delete {id} [--force]`
   - `php artisan tenant:list`
   - `php artisan tenant:seed {id}`

### FASE 12: Documentación Final 📝
1. README.md con:
   - Setup local (Docker + hosts file)
   - Setup Render (paso a paso)
   - Comandos importantes
   - Troubleshooting común
2. DEPLOYMENT.md con:
   - Checklist pre-deploy
   - Comandos de deploy
   - Rollback procedure
   - Monitoring y logs
3. BACKUP.md con:
   - Política de backups
   - Rotación de backups
   - Proceso de restauración
   - Disaster recovery

## Decisiones Técnicas

### ¿Por qué DATABASE para sesiones en lugar de FILE?
- FILE puede tener problemas con FilesystemTenancyBootstrapper
- DATABASE garantiza aislamiento perfecto por tenant
- Mejor para producción con load balancing

### ¿Por qué subdominios en lugar de paths?
- Mejor aislamiento de sesiones/cookies
- Más profesional para clientes SaaS
- Evita problemas de CORS

### ¿MySQL o PostgreSQL?
- Actualmente: MySQL (Laravel Sail)
- Render soporta ambos
- MySQL: más común, ecosistema Laravel
- PostgreSQL: mejor para schemas por tenant (alternativa futura)

## Cronograma Estimado
- Fases 1-4: 2-3 horas (configuración base)
- Fase 5: 2 horas (registro de tenants)
- Fases 6-7: 2 horas (storage y jobs)
- Fase 8: 1 hora (backups)
- Fase 9: 3 horas (tests completos)
- Fase 10: 2 horas (deploy Render)
- Fases 11-12: 2 horas (docs y seguridad)

**Total estimado: 14-16 horas**

## Entregables Finales
1. ✅ Código completo en branch `feature/multi-tenancy`
2. ✅ Tests verdes (PHPUnit + Feature tests)
3. ✅ Scripts de backup/restore
4. ✅ Documentación completa (README, DEPLOYMENT, BACKUP)
5. ✅ PR listo para merge
6. ✅ Aplicación desplegada en Render (opcional demo)

## Siguiente Paso
Comenzar con FASE 1: Instalación de stancl/tenancy
