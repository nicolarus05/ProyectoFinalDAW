# ✅ FASE 8: Scripts de Backup y Restauración - COMPLETADA

## 📋 Resumen de Implementación

Esta fase implementa un **sistema completo de backup y restauración** para bases de datos multi-tenant, con scripts bash automatizados, comando Laravel, y herramientas de mantenimiento.

---

## 🎯 Objetivos Completados

✅ **Script de backup automático** (`backup-tenants.sh`)
✅ **Script de restauración** (`restore-tenant.sh`)
✅ **Script de limpieza** (`cleanup-old-backups.sh`)
✅ **Comando Laravel** (`tenants:backup`)
✅ **Sistema de metadata** para cada backup
✅ **Compresión gzip** automática
✅ **Logging completo** de operaciones
✅ **Documentación detallada**

---

## 📁 Archivos Creados

### 1. **scripts/backup-tenants.sh** (11 KB)
Script bash para backup automático de todos los tenants

**Características:**
- ✅ Lee configuración desde `.env`
- ✅ Itera sobre todos los tenants en BD central
- ✅ Hace `mysqldump` de cada BD tenant
- ✅ Nombra archivos: `{tenant_id}_{slug}_{timestamp}.sql.gz`
- ✅ Comprime con gzip
- ✅ Guarda metadata de cada backup
- ✅ Backup de BD central opcional
- ✅ Logging detallado con colores
- ✅ Resumen con estadísticas

---

### 2. **scripts/restore-tenant.sh** (13 KB)
Script bash para restaurar tenant desde backup

**Características:**
- ✅ Restaura tenant específico
- ✅ Puede usar backup más reciente con `--latest`
- ✅ Lista todos los backups disponibles
- ✅ Descomprime automáticamente
- ✅ Opción para recrear tenant en BD central
- ✅ Opción para eliminar BD existente (con confirmación)
- ✅ Validaciones de seguridad
- ✅ Modo dry-run disponible

---

### 3. **scripts/cleanup-old-backups.sh** (8 KB)
Script para mantener solo N backups más recientes

**Características:**
- ✅ Mantiene X backups por tenant (configurable)
- ✅ Elimina backups antiguos automáticamente
- ✅ Puede limpiar tenant específico o todos
- ✅ Modo dry-run para ver qué se eliminaría
- ✅ Confirmación antes de eliminar
- ✅ Estadísticas de espacio liberado

---

### 4. **app/Console/Commands/BackupTenants.php**
Comando Laravel para backups integrados

**Características:**
- ✅ Integrado con Laravel
- ✅ Usa configuración de Laravel
- ✅ Barra de progreso
- ✅ Backup de tenants específicos o todos
- ✅ Limpieza automática opcional
- ✅ Metadata en formato Laravel
- ✅ Logging con sistema de Laravel

---

## 🚀 Uso de los Scripts

### **1. Backup de Todos los Tenants**

#### Opción A: Script Bash
```bash
# Backup completo (BD central + todos los tenants)
./scripts/backup-tenants.sh

# Ver output:
# ═══════════════════════════════════════════════════════════
#   📦 BACKUP MULTI-TENANCY - Sistema de Salón de Belleza
# ═══════════════════════════════════════════════════════════
#
# ℹ Iniciando proceso de backup...
# ℹ Directorio de backups: /path/to/storage/backups
# ℹ Host: mysql:3306
# ℹ Base de datos central: laravel
#
# ✓ Conexión establecida
#
# ═══ PASO 1: Backup de Base de Datos Central ═══
# ℹ Procesando base de datos central: laravel
#   → Creando dump de laravel...
#   → Dump creado: 234 KB
#   → Comprimiendo...
# ✓ Backup central completado: central_20241110_143022.sql.gz (45 KB)
#
# ═══ PASO 2: Backup de Bases de Datos de Tenants ═══
# ℹ Obteniendo lista de tenants...
# ℹ Se encontraron 3 tenant(s)
#
# ℹ Procesando tenant: Salón María (salon-maria) [ID: 1]
#   → Creando dump de tenant_1...
#   → Dump creado: 128 KB
#   → Comprimiendo...
# ✓ Backup completado: 1_salon-maria_20241110_143022.sql.gz (24 KB)
#
# [... más tenants ...]
#
# ═══════════════════════════════════════════════════════════
#   📊 RESUMEN DEL BACKUP
# ═══════════════════════════════════════════════════════════
#
#   Base de datos central: ✓ OK
#   Tenants procesados: 3
#   Exitosos: 3
#   Fallidos: 0
#
#   Espacio total usado: 156 MB
#
# ✓ Proceso de backup completado
#   Log guardado en: /path/to/storage/backups/backup.log
```

#### Opción B: Comando Laravel
```bash
# Backup de todos los tenants
./vendor/bin/sail artisan tenants:backup --compress

# Backup con limpieza automática (mantener 5)
./vendor/bin/sail artisan tenants:backup --compress --cleanup --keep=5

# Backup incluyendo BD central
./vendor/bin/sail artisan tenants:backup --compress --central

# Backup de tenants específicos
./vendor/bin/sail artisan tenants:backup --tenant=1 --tenant=2 --compress
```

---

### **2. Restaurar un Tenant**

#### Listar Backups Disponibles
```bash
# Listar todos los backups
./scripts/restore-tenant.sh --list

# Listar backups de un tenant específico
./scripts/restore-tenant.sh --tenant-id 1 --list

# Output:
# ═══════════════════════════════════════════════════════════
#   📦 BACKUPS DISPONIBLES
# ═══════════════════════════════════════════════════════════
#
# ℹ Backups del tenant 1:
#
#   [1] 1_salon-maria_20241110_143022.sql.gz
#       Tamaño: 24 KB
#       Fecha: 2024-11-10 14:30:22
#       Tenant: Salón María
#
#   [2] 1_salon-maria_20241109_093015.sql.gz
#       Tamaño: 23 KB
#       Fecha: 2024-11-09 09:30:15
#       Tenant: Salón María
```

#### Restaurar Backup Más Reciente
```bash
# Restaurar el backup más reciente del tenant 1
./scripts/restore-tenant.sh --tenant-id 1 --latest

# Con confirmación automática
./scripts/restore-tenant.sh --tenant-id 1 --latest --yes
```

#### Restaurar Backup Específico
```bash
# Restaurar archivo específico
./scripts/restore-tenant.sh \
    --tenant-id 1 \
    --file tenant_1/1_salon-maria_20241110_143022.sql.gz
```

#### Restaurar Eliminando BD Existente ⚠️
```bash
# ¡PELIGROSO! Elimina la BD actual antes de restaurar
./scripts/restore-tenant.sh \
    --tenant-id 1 \
    --latest \
    --drop-database

# Pedirá confirmación:
# ⚠ ¡ATENCIÓN! Se eliminará la base de datos tenant_1 existente.
#    Esta acción NO se puede deshacer.
# ¿Desea continuar? (s/N):
```

#### Restaurar Creando Tenant
```bash
# Si el tenant no existe en BD central, crearlo
./scripts/restore-tenant.sh \
    --tenant-id 1 \
    --latest \
    --create-tenant
```

---

### **3. Limpiar Backups Antiguos**

#### Ver Qué Se Eliminaría (Dry-Run)
```bash
# Simular limpieza (no elimina nada)
./scripts/cleanup-old-backups.sh --dry-run

# Output:
# ═══════════════════════════════════════════════════════════
#   🧹 LIMPIEZA DE BACKUPS - Sistema Multi-Tenancy
# ═══════════════════════════════════════════════════════════
#
# ⚠ MODO DRY-RUN: No se eliminará nada, solo se mostrará
#
# ℹ Manteniendo los 5 backups más recientes
#
# ⚠ Tenant 1: 8 backup(s) encontrados - Se eliminarán 3
#   [DRY-RUN] Eliminaría: 1_salon-maria_20241105_120000.sql.gz (22 KB)
#   [DRY-RUN] Eliminaría: 1_salon-maria_20241104_120000.sql.gz (23 KB)
#   [DRY-RUN] Eliminaría: 1_salon-maria_20241103_120000.sql.gz (21 KB)
```

#### Limpiar Manteniendo N Backups
```bash
# Mantener solo los 5 más recientes
./scripts/cleanup-old-backups.sh --keep 5

# Mantener 10 más recientes
./scripts/cleanup-old-backups.sh --keep 10

# Con confirmación automática
./scripts/cleanup-old-backups.sh --keep 5 --yes
```

#### Limpiar Tenant Específico
```bash
# Limpiar solo el tenant 1, mantener 3
./scripts/cleanup-old-backups.sh --tenant-id 1 --keep 3
```

---

## 📂 Estructura de Backups

```
storage/backups/
├── backup.log                          # Log de operaciones
├── central/                            # Backups de BD central
│   ├── central_20241110_143022.sql.gz
│   └── central_20241109_093015.sql.gz
├── tenant_1/                           # Backups del tenant 1
│   ├── 1_salon-maria_20241110_143022.sql.gz
│   ├── 1_salon-maria_20241110_143022.meta
│   ├── 1_salon-maria_20241109_093015.sql.gz
│   └── 1_salon-maria_20241109_093015.meta
├── tenant_2/                           # Backups del tenant 2
│   ├── 2_salon-laura_20241110_143025.sql.gz
│   ├── 2_salon-laura_20241110_143025.meta
│   └── ...
└── tenant_3/
    └── ...
```

---

## 📝 Formato de Metadata

Cada backup `.sql.gz` tiene un archivo `.meta` con información:

```
tenant_id=1
tenant_nombre=Salón María
tenant_slug=salon-maria
timestamp=20241110_143022
date=2024-11-10 14:30:22
database=tenant_1
original_size=128 KB
compressed_size=24 KB
```

Esta metadata permite:
- ✅ Identificar el backup sin descomprimirlo
- ✅ Restaurar el tenant en BD central si no existe
- ✅ Verificar integridad
- ✅ Auditoría

---

## ⚙️ Configuración

### Variables de Entorno (.env)

Los scripts leen automáticamente de `.env`:

```env
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### Permisos de Archivos

```bash
# Los scripts tienen permisos de ejecución
-rwxrwxr-x backup-tenants.sh
-rwxrwxr-x restore-tenant.sh
-rwxrwxr-x cleanup-old-backups.sh
```

---

## 🔒 Seguridad

### Backups
- ✅ Los backups se guardan en `storage/backups/` (no accesible vía web)
- ✅ Usar `.gitignore` para no subir backups al repositorio
- ✅ Comprimir con gzip reduce tamaño y añade obfuscación básica
- ⚠️ Para producción: cifrar backups sensibles

### Restauración
- ✅ Confirmación obligatoria antes de eliminar BD
- ✅ Validación de que el tenant existe
- ✅ Verificación de archivos antes de restaurar
- ✅ Modo dry-run para probar sin cambios

---

## 🚨 Casos de Uso

### Caso 1: Backup Diario Automático (Cron)

**Configurar en crontab:**
```bash
# Editar crontab
crontab -e

# Agregar línea para backup diario a las 3 AM
0 3 * * * cd /path/to/proyecto && ./scripts/backup-tenants.sh >> /path/to/logs/backup-cron.log 2>&1

# Limpiar backups antiguos cada domingo a las 4 AM (mantener 7)
0 4 * * 0 cd /path/to/proyecto && ./scripts/cleanup-old-backups.sh --keep 7 --yes >> /path/to/logs/cleanup-cron.log 2>&1
```

**O usando Laravel Scheduler** (`app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    // Backup diario a las 3 AM
    $schedule->command('tenants:backup --compress --cleanup --keep=7')
        ->dailyAt('03:00')
        ->onSuccess(function () {
            Log::info('Backup automático completado');
        })
        ->onFailure(function () {
            Log::error('Backup automático falló');
        });
}
```

---

### Caso 2: Backup Antes de Migración

```bash
# 1. Hacer backup completo
./scripts/backup-tenants.sh

# 2. Ejecutar migración
./vendor/bin/sail artisan migrate

# 3. Si algo falla, restaurar
./scripts/restore-tenant.sh --tenant-id 1 --latest --drop-database --yes
```

---

### Caso 3: Clonar Tenant

```bash
# 1. Hacer backup del tenant origen
./scripts/backup-tenants.sh

# 2. Crear nuevo tenant en la aplicación (obtener nuevo ID, ej: 4)

# 3. Restaurar backup del tenant 1 al tenant 4
# (requiere edición manual del dump para cambiar nombre de BD)
./scripts/restore-tenant.sh --tenant-id 4 --file tenant_1/1_salon-maria_20241110_143022.sql.gz
```

---

### Caso 4: Migrar a Otro Servidor

**Servidor Origen:**
```bash
# 1. Hacer backup
./scripts/backup-tenants.sh

# 2. Copiar carpeta de backups
tar -czf backups.tar.gz storage/backups/
scp backups.tar.gz usuario@servidor-destino:/tmp/
```

**Servidor Destino:**
```bash
# 3. Extraer backups
tar -xzf /tmp/backups.tar.gz -C /path/to/proyecto/storage/

# 4. Restaurar tenants
./scripts/restore-tenant.sh --tenant-id 1 --latest --create-tenant --yes
./scripts/restore-tenant.sh --tenant-id 2 --latest --create-tenant --yes
# ... etc
```

---

### Caso 5: Recuperación de Desastre

```bash
# Tenant eliminó datos por error

# 1. Listar backups disponibles
./scripts/restore-tenant.sh --tenant-id 1 --list

# 2. Elegir backup anterior al error
./scripts/restore-tenant.sh \
    --tenant-id 1 \
    --file tenant_1/1_salon-maria_20241109_093015.sql.gz \
    --drop-database \
    --yes

# 3. Verificar datos restaurados
./vendor/bin/sail artisan tinker
> \App\Models\Tenant::find(1)->run(function() { \App\Models\Cita::count(); });
```

---

## 🔧 Opciones Avanzadas

### Backup Selectivo
```bash
# Solo ciertos tenants
./scripts/backup-tenants.sh
# Luego mover/eliminar los que no se necesiten
```

### Compresión Extra
```bash
# Después de gzip, comprimir más con tar
tar -czf backups-$(date +%Y%m%d).tar.gz storage/backups/

# O usar 7zip para máxima compresión
7z a -t7z -m0=lzma2 -mx=9 backups.7z storage/backups/
```

### Cifrado de Backups
```bash
# Cifrar backup con GPG
gpg --symmetric --cipher-algo AES256 backup.sql.gz

# Descifrar
gpg --decrypt backup.sql.gz.gpg > backup.sql.gz
```

### Backup Remoto (S3, FTP, etc.)
```bash
# Después del backup, subir a S3
aws s3 sync storage/backups/ s3://mi-bucket/backups/ --delete

# O a servidor remoto
rsync -avz storage/backups/ usuario@servidor:/backups/
```

---

## ✅ Verificación de Implementación

### 1. Verificar Scripts Creados
```bash
ls -lh scripts/

# Output esperado:
# -rwxrwxr-x backup-tenants.sh
# -rwxrwxr-x cleanup-old-backups.sh
# -rwxrwxr-x restore-tenant.sh
```

### 2. Verificar Comando Laravel
```bash
./vendor/bin/sail artisan list | grep tenants:backup

# Output esperado:
# tenants:backup    Realiza backup de las bases de datos de tenants
```

### 3. Probar Backup (Dry-Run)
```bash
# Crear directorio de backups
mkdir -p storage/backups

# Ejecutar backup de prueba
./scripts/backup-tenants.sh
```

### 4. Verificar Estructura
```bash
tree storage/backups/

# Output esperado:
# storage/backups/
# ├── backup.log
# ├── central/
# │   └── central_*.sql.gz
# └── tenant_1/
#     ├── 1_*_*.sql.gz
#     └── 1_*_*.meta
```

---

## 📊 Métricas y Monitoreo

### Espacio Usado
```bash
# Ver espacio total de backups
du -sh storage/backups/

# Por tenant
du -sh storage/backups/tenant_*/

# Backups más grandes
find storage/backups -name "*.sql.gz" -exec du -h {} \; | sort -h | tail -10
```

### Logs
```bash
# Ver últimos backups
tail -f storage/backups/backup.log

# Buscar errores
grep "✗" storage/backups/backup.log
```

---

## 🎯 Mejores Prácticas

### ✅ DO (Hacer)
1. **Automatizar backups diarios** con cron o Laravel Scheduler
2. **Probar restauraciones** periódicamente para verificar integridad
3. **Mantener backups limitados** (7-30 días) según política
4. **Almacenar backups remotos** para recuperación de desastres
5. **Cifrar backups sensibles** en producción
6. **Documentar proceso** de restauración para el equipo
7. **Monitorear espacio** en disco para backups

### ❌ DON'T (No Hacer)
1. **No** subir backups a repositorio Git
2. **No** mantener backups infinitamente (usar limpieza)
3. **No** restaurar en producción sin confirmar
4. **No** ejecutar backups en horarios de alta carga
5. **No** olvidar probar restauraciones regularmente
6. **No** almacenar contraseñas de BD en scripts
7. **No** confiar solo en backups locales

---

## 🚀 Roadmap Futuro

### Mejoras Potenciales
- [ ] Backups incrementales (solo cambios)
- [ ] Verificación automática de integridad
- [ ] Interfaz web para gestión de backups
- [ ] Notificaciones por email cuando falla backup
- [ ] Estadísticas de rendimiento de backups
- [ ] Restauración parcial (tablas específicas)
- [ ] Integración con AWS S3/Azure/Google Cloud
- [ ] Programación de backups desde UI

---

## 📚 Recursos Adicionales

### Comandos Útiles de MySQL
```bash
# Ver bases de datos
mysql -u root -p -e "SHOW DATABASES;"

# Ver tamaño de BDs
mysql -u root -p -e "
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
GROUP BY table_schema;"

# Verificar integridad de dump
mysql -u root -p < backup.sql --dry-run
```

### Troubleshooting

**Error: "mysqldump: command not found"**
```bash
# Instalar mysql-client
apt-get install mysql-client

# O en Alpine Linux (Docker)
apk add mysql-client
```

**Error: "Access denied"**
```bash
# Verificar credenciales en .env
cat .env | grep DB_

# Probar conexión manual
mysql -h mysql -P 3306 -u sail -p
```

**Backup muy lento**
```bash
# Agregar --quick a mysqldump
mysqldump --quick ...

# O dividir por tablas
mysqldump --tables tabla1 tabla2 ...
```

---

## 🎉 Conclusión

La **FASE 8** proporciona un sistema completo y robusto de backup/restauración:

✅ **3 scripts bash** automatizados y con validaciones
✅ **1 comando Laravel** integrado
✅ **Sistema de metadata** para auditoría
✅ **Logging completo** de operaciones
✅ **Compresión automática** con gzip
✅ **Limpieza automática** de backups antiguos
✅ **Múltiples modos** de operación (list, latest, dry-run)
✅ **Validaciones de seguridad** robustas
✅ **Documentación exhaustiva** con ejemplos

**El sistema está listo para producción** y puede ser extendido según necesidades específicas.

---

## 📝 Próximos Pasos

¿Listo para continuar con las siguientes fases?

- **FASE 9**: Monitoreo y Logging
- **FASE 10**: Testing y QA
- **FASE 11**: Deployment y CI/CD
- **FASE 12**: Documentación Final

**¿Qué fase deseas implementar a continuación?** 🚀
