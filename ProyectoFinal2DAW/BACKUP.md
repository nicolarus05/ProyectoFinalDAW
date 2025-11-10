# 💾 BACKUP - Política de Backups y Disaster Recovery

Guía completa para backups, restauración y recuperación ante desastres del sistema multi-tenant.

---

## 📋 Índice

- [Estrategia de Backup](#-estrategia-de-backup)
- [Tipos de Backup](#-tipos-de-backup)
- [Scripts de Backup](#-scripts-de-backup)
- [Restauración](#-restauración)
- [Automatización](#-automatización)
- [Almacenamiento](#-almacenamiento)
- [Disaster Recovery](#-disaster-recovery)
- [Testing de Backups](#-testing-de-backups)
- [Troubleshooting](#-troubleshooting)

---

## 🎯 Estrategia de Backup

### Política 3-2-1

**3** copias de los datos  
**2** tipos de medios diferentes  
**1** copia off-site (fuera del servidor)

### Frecuencias Recomendadas

| Tipo | Frecuencia | Retención | Prioridad |
|------|-----------|-----------|-----------|
| **BD Tenants** | Diario (2 AM) | 30 días | 🔴 CRÍTICA |
| **BD Central** | Diario (2 AM) | 30 días | 🔴 CRÍTICA |
| **Archivos Storage** | Semanal | 90 días | 🟡 MEDIA |
| **Código** | Git push | Permanente | 🟢 BAJA |
| **Pre-eliminación** | Automático | 90 días | 🔴 CRÍTICA |

### RPO y RTO

- **RPO (Recovery Point Objective)**: Máximo 24 horas de pérdida de datos
- **RTO (Recovery Time Objective)**: Restauración en < 2 horas

---

## 📦 Tipos de Backup

### 1. Backup Automático Pre-Eliminación

Cuando se elimina un tenant (soft o force delete), se crea automáticamente un backup.

**Ubicación**: `storage/backups/deletion_{tenant_id}_{timestamp}.sql.gz`

**Comandos que lo activan**:
```bash
php artisan tenant:delete salon-maria
php artisan tenant:delete salon-maria --force
php artisan tenant:purge
```

**Características**:
- ✅ Automático (no puede olvidarse)
- ✅ Comprimido con gzip (ratio 10:1)
- ✅ Incluye estructura + datos
- ✅ Nombre descriptivo con timestamp

### 2. Backup Manual de Tenant Específico

Crear backup de un tenant específico antes de operación arriesgada.

```bash
./scripts/backup-tenants.sh tenant_salon_maria
```

**Resultado**:
```
storage/backups/manual_tenant_salon_maria_20250110_143022.sql.gz
```

### 3. Backup de Todos los Tenants

Backup completo de todos los tenants del sistema.

```bash
./scripts/backup-tenants.sh
```

**Resultado**:
```
storage/backups/
├── all_tenant_salon_maria_20250110_020000.sql.gz
├── all_tenant_barberia_lopez_20250110_020001.sql.gz
├── all_tenant_peluqueria_ana_20250110_020002.sql.gz
└── ...
```

### 4. Backup Base de Datos Central

Backup de la BD central (tenants, domains, cache, jobs).

```bash
mysqldump -u usuario -p salon_central | gzip > storage/backups/central_20250110_020000.sql.gz
```

### 5. Backup de Archivos

Backup del directorio `storage` (uploads, logs, etc.).

```bash
tar -czf storage_backup_20250110.tar.gz storage/app/tenants/
```

---

## 🔧 Scripts de Backup

### Script: backup-tenants.sh

Ubicación: `scripts/backup-tenants.sh`

**Uso**:

```bash
# Backup de todos los tenants
./scripts/backup-tenants.sh

# Backup de tenant específico
./scripts/backup-tenants.sh tenant_salon_maria

# Con output verbose
./scripts/backup-tenants.sh --verbose
```

**Contenido del Script**:

```bash
#!/bin/bash
# scripts/backup-tenants.sh

set -e  # Salir si hay error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuración
BACKUP_DIR="storage/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_USER="${DB_USERNAME:-sail}"
DB_PASS="${DB_PASSWORD:-password}"
DB_HOST="${DB_HOST:-mysql}"

# Crear directorio si no existe
mkdir -p "$BACKUP_DIR"

# Función de backup
backup_database() {
    local db_name=$1
    local backup_file="$BACKUP_DIR/${2:-all}_${db_name}_${TIMESTAMP}.sql.gz"
    
    echo -e "${YELLOW}📦 Backing up: $db_name${NC}"
    
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        "$db_name" | gzip > "$backup_file"
    
    if [ $? -eq 0 ]; then
        local size=$(du -h "$backup_file" | cut -f1)
        echo -e "${GREEN}✅ Backup completado: $backup_file ($size)${NC}"
    else
        echo -e "${RED}❌ Error en backup de $db_name${NC}"
        return 1
    fi
}

# Si se especifica un tenant, hacer backup solo de ese
if [ -n "$1" ] && [ "$1" != "--verbose" ]; then
    backup_database "$1" "manual"
    exit 0
fi

# Obtener lista de bases de datos tenant
echo -e "${YELLOW}🔍 Buscando tenants...${NC}"
TENANT_DBS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SHOW DATABASES LIKE 'tenant%';" -s --skip-column-names)

if [ -z "$TENANT_DBS" ]; then
    echo -e "${RED}❌ No se encontraron bases de datos tenant${NC}"
    exit 1
fi

# Backup de cada tenant
total=0
success=0
for db in $TENANT_DBS; do
    total=$((total + 1))
    if backup_database "$db" "all"; then
        success=$((success + 1))
    fi
done

# Resumen
echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}📊 Resumen de Backups${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "Total tenants: $total"
echo -e "Exitosos: ${GREEN}$success${NC}"
echo -e "Fallidos: ${RED}$((total - success))${NC}"
echo -e "Directorio: $BACKUP_DIR"
echo ""

# Limpiar backups antiguos (más de 30 días)
echo -e "${YELLOW}🧹 Limpiando backups antiguos (>30 días)...${NC}"
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +30 -delete
echo -e "${GREEN}✅ Limpieza completada${NC}"
```

**Permisos**:

```bash
chmod +x scripts/backup-tenants.sh
```

### Script: cleanup-old-backups.sh

Ubicación: `scripts/cleanup-old-backups.sh`

**Uso**:

```bash
# Eliminar backups > 30 días
./scripts/cleanup-old-backups.sh

# Eliminar backups > 60 días
./scripts/cleanup-old-backups.sh 60
```

**Contenido**:

```bash
#!/bin/bash
# scripts/cleanup-old-backups.sh

DAYS=${1:-30}
BACKUP_DIR="storage/backups"

echo "🧹 Eliminando backups de más de $DAYS días..."

# Contar archivos antes
before=$(find "$BACKUP_DIR" -name "*.sql.gz" | wc -l)

# Eliminar
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$DAYS -delete

# Contar después
after=$(find "$BACKUP_DIR" -name "*.sql.gz" | wc -l)
deleted=$((before - after))

echo "✅ Eliminados: $deleted archivos"
echo "📁 Restantes: $after backups"
```

---

## 🔄 Restauración

### Script: restore-tenant.sh

Ubicación: `scripts/restore-tenant.sh`

**Uso**:

```bash
# Restaurar tenant desde backup
./scripts/restore-tenant.sh storage/backups/backup_salon_maria_20250110.sql.gz tenant_salon_maria

# Con recreación de BD
./scripts/restore-tenant.sh storage/backups/backup_salon_maria_20250110.sql.gz tenant_salon_maria --recreate
```

**Contenido del Script**:

```bash
#!/bin/bash
# scripts/restore-tenant.sh

set -e

# Validar argumentos
if [ $# -lt 2 ]; then
    echo "❌ Uso: $0 <archivo_backup.sql.gz> <nombre_bd> [--recreate]"
    echo "Ejemplo: $0 storage/backups/backup_salon_maria.sql.gz tenant_salon_maria"
    exit 1
fi

BACKUP_FILE=$1
DB_NAME=$2
RECREATE=$3

# Configuración
DB_USER="${DB_USERNAME:-sail}"
DB_PASS="${DB_PASSWORD:-password}"
DB_HOST="${DB_HOST:-mysql}"

# Validar que existe el archivo
if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Error: Archivo no encontrado: $BACKUP_FILE"
    exit 1
fi

echo "🔄 Iniciando restauración..."
echo "   Archivo: $BACKUP_FILE"
echo "   Base de datos: $DB_NAME"
echo ""

# Confirmar
read -p "⚠️  ¿Estás seguro? Esto SOBRESCRIBIRÁ la base de datos '$DB_NAME'. [y/N] " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Restauración cancelada"
    exit 1
fi

# Recrear BD si se solicita
if [ "$RECREATE" == "--recreate" ]; then
    echo "🗑️  Eliminando base de datos existente..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;"
    
    echo "📁 Creando base de datos nueva..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
fi

# Restaurar
echo "📥 Restaurando datos..."
gunzip < "$BACKUP_FILE" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Restauración completada exitosamente"
    echo "   Base de datos: $DB_NAME"
    
    # Verificar tablas
    table_count=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" -s --skip-column-names)
    echo "   Tablas restauradas: $table_count"
else
    echo ""
    echo "❌ Error en la restauración"
    exit 1
fi
```

### Proceso de Restauración Manual

#### 1. Restaurar Tenant Eliminado Accidentalmente

```bash
# Paso 1: Encontrar el backup más reciente
ls -lth storage/backups/deletion_salon_maria_*.sql.gz | head -1

# Paso 2: Restaurar la BD
./scripts/restore-tenant.sh storage/backups/deletion_salon_maria_20250110.sql.gz tenant_salon_maria --recreate

# Paso 3: Recrear el tenant en BD central
php artisan tinker
>>> $tenant = App\Models\Tenant::create(['id' => 'salon-maria']);
>>> $tenant->domains()->create(['domain' => 'salon-maria.tudominio.com']);

# Paso 4: Verificar
php artisan tenant:list
```

#### 2. Restaurar desde Backup Programado

```bash
# Paso 1: Listar backups disponibles
ls -lh storage/backups/all_tenant_salon_maria_*.sql.gz

# Paso 2: Elegir y restaurar
./scripts/restore-tenant.sh storage/backups/all_tenant_salon_maria_20250109_020000.sql.gz tenant_salon_maria

# Paso 3: Verificar integridad
php artisan tenants:run salon-maria -- db:table users
```

#### 3. Restaurar Base de Datos Central

```bash
# Backup de seguridad primero
mysqldump -u root -p salon_central | gzip > central_antes_restore.sql.gz

# Restaurar
gunzip < storage/backups/central_20250109_020000.sql.gz | mysql -u root -p salon_central

# Verificar tenants
php artisan tenant:list
```

---

## ⏰ Automatización

### Configurar Cron Jobs

```bash
# Editar crontab
crontab -e
```

**Añadir**:

```cron
# Backup diario de todos los tenants (2:00 AM)
0 2 * * * cd /var/www/html && ./scripts/backup-tenants.sh >> /var/log/tenant-backup.log 2>&1

# Backup semanal de BD central (Domingos 3:00 AM)
0 3 * * 0 cd /var/www/html && mysqldump -u usuario -p'password' salon_central | gzip > storage/backups/central_$(date +\%Y\%m\%d).sql.gz

# Limpieza mensual de backups antiguos (Primer día del mes, 4:00 AM)
0 4 1 * * cd /var/www/html && ./scripts/cleanup-old-backups.sh 30 >> /var/log/backup-cleanup.log 2>&1

# Purga automática de tenants vencidos (Diario 3:00 AM)
0 3 * * * cd /var/www/html && php artisan tenant:purge --force >> /var/log/tenant-purge.log 2>&1
```

### Verificar Cron

```bash
# Ver cron jobs activos
crontab -l

# Ver logs de cron
grep CRON /var/log/syslog

# Ver logs específicos de backups
tail -f /var/log/tenant-backup.log
```

### Script de Verificación de Backups

```bash
#!/bin/bash
# scripts/verify-backups.sh

BACKUP_DIR="storage/backups"
TODAY=$(date +%Y%m%d)

# Verificar que existen backups de hoy
today_backups=$(find "$BACKUP_DIR" -name "*${TODAY}*.sql.gz" | wc -l)

if [ $today_backups -eq 0 ]; then
    echo "⚠️  ALERTA: No hay backups de hoy"
    # Enviar email de alerta (opcional)
    # mail -s "ALERTA: Backups faltantes" admin@tudominio.com <<< "No se encontraron backups de la fecha de hoy"
    exit 1
else
    echo "✅ OK: $today_backups backup(s) de hoy encontrados"
fi

# Verificar tamaño de backups
for backup in $(find "$BACKUP_DIR" -name "*${TODAY}*.sql.gz"); do
    size=$(stat -f%z "$backup" 2>/dev/null || stat -c%s "$backup")
    if [ $size -lt 1024 ]; then
        echo "⚠️  ALERTA: Backup sospechosamente pequeño: $backup ($size bytes)"
    fi
done
```

Añadir al cron:

```cron
# Verificar backups diarios (6:00 AM)
0 6 * * * cd /var/www/html && ./scripts/verify-backups.sh >> /var/log/backup-verification.log 2>&1
```

---

## 📁 Almacenamiento

### Opción 1: Local (Desarrollo/Pequeña Escala)

```bash
# Directorio local
storage/backups/

# Pros: Simple, rápido, gratis
# Contras: Sin redundancia, se pierde si el servidor falla
```

### Opción 2: Amazon S3 (Producción Recomendado)

**Configurar AWS CLI**:

```bash
# Instalar
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip"
unzip awscliv2.zip
sudo ./aws/install

# Configurar credenciales
aws configure
# AWS Access Key ID: ...
# AWS Secret Access Key: ...
# Default region: us-east-1
# Default output format: json
```

**Script de sync a S3**:

```bash
#!/bin/bash
# scripts/sync-to-s3.sh

BACKUP_DIR="storage/backups"
S3_BUCKET="s3://tu-bucket-backups/tenants/"

echo "☁️  Sincronizando backups a S3..."

aws s3 sync "$BACKUP_DIR" "$S3_BUCKET" \
    --storage-class STANDARD_IA \
    --exclude "*" \
    --include "*.sql.gz"

echo "✅ Sincronización completada"
```

**Añadir al cron**:

```cron
# Sync a S3 diario (5:00 AM, después de backups)
0 5 * * * cd /var/www/html && ./scripts/sync-to-s3.sh >> /var/log/s3-sync.log 2>&1
```

**Lifecycle Policy (S3)**:

```json
{
  "Rules": [
    {
      "Id": "MoverAGlacierDespues30Dias",
      "Status": "Enabled",
      "Transitions": [
        {
          "Days": 30,
          "StorageClass": "GLACIER"
        }
      ],
      "Expiration": {
        "Days": 365
      }
    }
  ]
}
```

### Opción 3: Dropbox/Google Drive

**Con rclone**:

```bash
# Instalar
curl https://rclone.org/install.sh | sudo bash

# Configurar
rclone config
# Seguir wizard para Dropbox o Google Drive

# Sincronizar
rclone copy storage/backups/ dropbox:SalonBackups/
```

---

## 🚨 Disaster Recovery

### Escenario 1: Servidor Completamente Perdido

**Plan de Recuperación**:

1. **Aprovisionar nuevo servidor** (mismo SO y versiones)

2. **Clonar repositorio**:
   ```bash
   git clone https://github.com/tu-usuario/salon-saas.git
   cd salon-saas
   ```

3. **Restaurar .env**:
   ```bash
   cp backup/.env.production .env
   php artisan key:generate
   ```

4. **Instalar dependencias**:
   ```bash
   composer install --no-dev
   npm install && npm run build
   ```

5. **Restaurar BD Central desde S3**:
   ```bash
   aws s3 cp s3://tu-bucket/central_20250109.sql.gz .
   gunzip < central_20250109.sql.gz | mysql -u root -p salon_central
   ```

6. **Restaurar BDs de Tenants**:
   ```bash
   aws s3 sync s3://tu-bucket/tenants/ storage/backups/
   
   for backup in storage/backups/all_tenant_*.sql.gz; do
       db_name=$(basename "$backup" | sed 's/all_\(.*\)_[0-9]*.sql.gz/\1/')
       ./scripts/restore-tenant.sh "$backup" "$db_name" --recreate
   done
   ```

7. **Verificar**:
   ```bash
   php artisan tenant:list
   php artisan config:cache
   php artisan route:cache
   ```

8. **Poner en línea**:
   ```bash
   php artisan up
   ```

**Tiempo estimado**: 2-4 horas (dependiendo del tamaño)

### Escenario 2: Corrupción de BD de un Tenant

**Plan de Recuperación**:

1. **Identificar el problema**:
   ```bash
   php artisan tenants:run salon-corrupto -- db:table users
   # Error: Table doesn't exist
   ```

2. **Encontrar último backup bueno**:
   ```bash
   ls -lth storage/backups/ | grep salon_corrupto | head -5
   ```

3. **Restaurar**:
   ```bash
   ./scripts/restore-tenant.sh storage/backups/all_tenant_salon_corrupto_20250108.sql.gz tenant_salon_corrupto
   ```

4. **Verificar**:
   ```bash
   php artisan tenants:run salon-corrupto -- db:table users
   ```

**Tiempo estimado**: 10-30 minutos

### Escenario 3: Eliminación Accidental de Tenant

**Plan de Recuperación**:

1. **Verificar soft delete**:
   ```bash
   php artisan tenant:list --only-deleted
   ```

2. **Si está soft-deleted, restaurar**:
   ```bash
   php artisan tinker
   >>> App\Models\Tenant::withTrashed()->find('salon-id')->restore();
   ```

3. **Si fue force-deleted, restaurar desde backup**:
   ```bash
   # Buscar backup de eliminación
   ls storage/backups/deletion_salon_id_*.sql.gz
   
   # Recrear tenant
   php artisan tenant:create salon-id salon-id.tudominio.com --name="Salón Recuperado"
   
   # Restaurar datos
   ./scripts/restore-tenant.sh storage/backups/deletion_salon_id_20250110.sql.gz tenant_salon_id
   ```

**Tiempo estimado**: 5-15 minutos

---

## 🧪 Testing de Backups

### Test Mensual de Restauración

**Calendario recomendado**: Primer sábado de cada mes

**Procedimiento**:

```bash
# 1. Crear servidor de prueba
# (DigitalOcean Droplet, AWS EC2, etc.)

# 2. Descargar último backup
scp usuario@servidor-prod:/var/www/html/storage/backups/all_tenant_test_*.sql.gz .

# 3. Restaurar en servidor de prueba
./scripts/restore-tenant.sh all_tenant_test_20250109.sql.gz tenant_test --recreate

# 4. Verificar datos
php artisan tenants:run test -- db:table users
mysql -e "SELECT COUNT(*) FROM tenant_test.users;"

# 5. Verificar integridad
php artisan tenants:run test -- migrate:status

# 6. Documentar resultado
echo "$(date): Test de restauración OK" >> backup-tests.log

# 7. Destruir servidor de prueba
```

### Verificación de Integridad

```bash
#!/bin/bash
# scripts/verify-backup-integrity.sh

BACKUP_FILE=$1

if [ ! -f "$BACKUP_FILE" ]; then
    echo "❌ Archivo no encontrado"
    exit 1
fi

echo "🔍 Verificando integridad de $BACKUP_FILE..."

# Test 1: Puede descomprimirse
if gunzip -t "$BACKUP_FILE"; then
    echo "✅ Compresión OK"
else
    echo "❌ Archivo corrupto"
    exit 1
fi

# Test 2: Contiene SQL válido
if gunzip < "$BACKUP_FILE" | head -100 | grep -q "CREATE TABLE"; then
    echo "✅ Contiene SQL válido"
else
    echo "⚠️  No se detectaron statements CREATE TABLE"
fi

# Test 3: Tamaño razonable
size=$(stat -c%s "$BACKUP_FILE")
if [ $size -gt 10240 ]; then  # > 10KB
    echo "✅ Tamaño razonable: $(numfmt --to=iec-i --suffix=B $size)"
else
    echo "⚠️  Archivo sospechosamente pequeño: $size bytes"
fi

echo "✅ Verificación completada"
```

---

## 🐛 Troubleshooting

### Error: "mysqldump: command not found"

**Solución**:
```bash
# Ubuntu/Debian
sudo apt-get install mysql-client

# Docker Sail
./vendor/bin/sail shell
apt-get update && apt-get install -y default-mysql-client
```

### Error: "gzip: stdout: Broken pipe"

**Solución**:
```bash
# Aumentar límites del sistema
ulimit -n 4096

# O desactivar compresión para debug
mysqldump ... > backup.sql  # Sin gzip
```

### Error: Backup muy lento

**Solución**:
```bash
# Añadir flags de optimización
mysqldump --single-transaction --quick --skip-lock-tables ...
```

### Error: "Access denied" al restaurar

**Solución**:
```bash
# Verificar permisos del usuario
mysql -u root -p
GRANT ALL PRIVILEGES ON *.* TO 'usuario'@'%';
FLUSH PRIVILEGES;
```

---

## 📊 Checklist de Backups

### Diario
- [ ] Backup automático ejecutado (verificar logs)
- [ ] Backups sincronizados a S3/almacenamiento remoto
- [ ] Verificación de integridad ejecutada

### Semanal
- [ ] Revisar espacio en disco
- [ ] Limpiar backups antiguos (>30 días)
- [ ] Verificar que cron jobs están activos

### Mensual
- [ ] Test de restauración completo
- [ ] Revisar política de retención
- [ ] Documentar resultados de tests
- [ ] Actualizar procedimientos si es necesario

---

## 📞 Soporte

- **Documentación**: [README_MULTITENANCY.md](README_MULTITENANCY.md)
- **Deployment**: [DEPLOYMENT.md](DEPLOYMENT.md)
- **Issues**: GitHub Issues

---

**Versión**: 1.0.0  
**Última actualización**: 10 de Noviembre de 2025  
**Estado**: ✅ Producción Ready
