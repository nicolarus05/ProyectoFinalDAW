#!/bin/bash

################################################################################
# Script: backup-tenants.sh
# Descripción: Realiza backup automático de todas las bases de datos de tenants
# Autor: Sistema Multi-Tenancy
# Versión: 1.0
################################################################################

set -e  # Detener si hay errores

# ============================================================================
# CONFIGURACIÓN
# ============================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración de base de datos (obtener de .env)
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_DIR/.env"

# Leer configuración de .env
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}✗ Error: Archivo .env no encontrado en $ENV_FILE${NC}"
    exit 1
fi

# Función para leer variable de .env
get_env_var() {
    local var_name=$1
    local value=$(grep "^${var_name}=" "$ENV_FILE" | cut -d '=' -f2- | tr -d '\r' | tr -d '"' | tr -d "'")
    echo "$value"
}

DB_HOST=$(get_env_var "DB_HOST")
DB_PORT=$(get_env_var "DB_PORT")
DB_DATABASE=$(get_env_var "DB_DATABASE")
DB_USERNAME=$(get_env_var "DB_USERNAME")
DB_PASSWORD=$(get_env_var "DB_PASSWORD")

# Directorio de backups
BACKUP_DIR="$PROJECT_DIR/storage/backups"
LOG_FILE="$BACKUP_DIR/backup.log"

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

# Timestamp para el backup
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DATE_READABLE=$(date +"%Y-%m-%d %H:%M:%S")

# ============================================================================
# FUNCIONES
# ============================================================================

log_message() {
    local message="[$DATE_READABLE] $1"
    echo "$message" >> "$LOG_FILE"
    echo -e "$2$1${NC}"
}

log_success() {
    log_message "✓ $1" "$GREEN"
}

log_error() {
    log_message "✗ $1" "$RED"
}

log_info() {
    log_message "ℹ $1" "$BLUE"
}

log_warning() {
    log_message "⚠ $1" "$YELLOW"
}

# Función para obtener lista de tenants desde la BD central
get_tenants() {
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -N -e "SELECT id FROM tenants ORDER BY id;" 2>/dev/null
}

# Función para obtener información del tenant
get_tenant_info() {
    local tenant_id=$1
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -N -e "SELECT nombre, slug FROM tenants WHERE id='$tenant_id';" 2>/dev/null
}

# Función para hacer backup de un tenant
backup_tenant() {
    local tenant_id=$1
    local tenant_db="tenant_${tenant_id}"
    
    # Obtener información del tenant
    local tenant_info=$(get_tenant_info "$tenant_id")
    local tenant_nombre=$(echo "$tenant_info" | cut -f1)
    local tenant_slug=$(echo "$tenant_info" | cut -f2)
    
    log_info "Procesando tenant: $tenant_nombre ($tenant_slug) [ID: $tenant_id]"
    
    # Verificar si la BD del tenant existe
    local db_exists=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW DATABASES LIKE '$tenant_db';" 2>/dev/null | grep "$tenant_db" || echo "")
    
    if [ -z "$db_exists" ]; then
        log_warning "Base de datos $tenant_db no existe, saltando..."
        return 1
    fi
    
    # Nombre del archivo de backup
    local backup_filename="${tenant_id}_${tenant_slug}_${TIMESTAMP}.sql"
    local backup_path="$BACKUP_DIR/$backup_filename"
    local backup_compressed="${backup_path}.gz"
    
    # Crear directorio para este tenant si no existe
    local tenant_backup_dir="$BACKUP_DIR/tenant_${tenant_id}"
    mkdir -p "$tenant_backup_dir"
    
    backup_path="$tenant_backup_dir/$backup_filename"
    backup_compressed="${backup_path}.gz"
    
    log_info "  → Creando dump de $tenant_db..."
    
    # Realizar dump de la base de datos
    if mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$tenant_db" > "$backup_path" 2>/dev/null; then
        
        # Obtener tamaño del dump
        local dump_size=$(du -h "$backup_path" | cut -f1)
        log_info "  → Dump creado: $dump_size"
        
        # Comprimir con gzip
        log_info "  → Comprimiendo..."
        if gzip -f "$backup_path" 2>/dev/null; then
            local compressed_size=$(du -h "$backup_compressed" | cut -f1)
            log_success "Backup completado: $backup_filename.gz ($compressed_size)"
            
            # Guardar metadata
            echo "tenant_id=$tenant_id" > "$tenant_backup_dir/${backup_filename}.meta"
            echo "tenant_nombre=$tenant_nombre" >> "$tenant_backup_dir/${backup_filename}.meta"
            echo "tenant_slug=$tenant_slug" >> "$tenant_backup_dir/${backup_filename}.meta"
            echo "timestamp=$TIMESTAMP" >> "$tenant_backup_dir/${backup_filename}.meta"
            echo "date=$DATE_READABLE" >> "$tenant_backup_dir/${backup_filename}.meta"
            echo "database=$tenant_db" >> "$tenant_backup_dir/${backup_filename}.meta"
            echo "original_size=$dump_size" >> "$tenant_backup_dir/${backup_filename}.meta"
            echo "compressed_size=$compressed_size" >> "$tenant_backup_dir/${backup_filename}.meta"
            
            return 0
        else
            log_error "Error al comprimir $backup_path"
            rm -f "$backup_path"
            return 1
        fi
    else
        log_error "Error al crear dump de $tenant_db"
        rm -f "$backup_path"
        return 1
    fi
}

# Función para hacer backup de la BD central
backup_central() {
    log_info "Procesando base de datos central: $DB_DATABASE"
    
    local backup_filename="central_${TIMESTAMP}.sql"
    local backup_dir="$BACKUP_DIR/central"
    mkdir -p "$backup_dir"
    
    local backup_path="$backup_dir/$backup_filename"
    local backup_compressed="${backup_path}.gz"
    
    log_info "  → Creando dump de $DB_DATABASE..."
    
    if mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_DATABASE" > "$backup_path" 2>/dev/null; then
        
        local dump_size=$(du -h "$backup_path" | cut -f1)
        log_info "  → Dump creado: $dump_size"
        
        log_info "  → Comprimiendo..."
        if gzip -f "$backup_path" 2>/dev/null; then
            local compressed_size=$(du -h "$backup_compressed" | cut -f1)
            log_success "Backup central completado: $backup_filename.gz ($compressed_size)"
            return 0
        else
            log_error "Error al comprimir backup central"
            rm -f "$backup_path"
            return 1
        fi
    else
        log_error "Error al crear dump de BD central"
        rm -f "$backup_path"
        return 1
    fi
}

# ============================================================================
# MAIN
# ============================================================================

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  📦 BACKUP MULTI-TENANCY - Sistema de Salón de Belleza"
echo "═══════════════════════════════════════════════════════════"
echo ""

log_info "Iniciando proceso de backup..."
log_info "Directorio de backups: $BACKUP_DIR"
log_info "Host: $DB_HOST:$DB_PORT"
log_info "Base de datos central: $DB_DATABASE"
echo ""

# Verificar conexión a MySQL
log_info "Verificando conexión a MySQL..."
if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" >/dev/null 2>&1; then
    log_error "No se puede conectar a MySQL. Verifica las credenciales."
    exit 1
fi
log_success "Conexión establecida"
echo ""

# Backup de BD central
log_info "═══ PASO 1: Backup de Base de Datos Central ═══"
if backup_central; then
    CENTRAL_SUCCESS=1
else
    CENTRAL_SUCCESS=0
fi
echo ""

# Obtener lista de tenants
log_info "═══ PASO 2: Backup de Bases de Datos de Tenants ═══"
log_info "Obteniendo lista de tenants..."
TENANTS=$(get_tenants)

if [ -z "$TENANTS" ]; then
    log_warning "No se encontraron tenants en la base de datos"
    TOTAL_TENANTS=0
else
    TOTAL_TENANTS=$(echo "$TENANTS" | wc -l)
    log_info "Se encontraron $TOTAL_TENANTS tenant(s)"
    echo ""
    
    # Contadores
    SUCCESS_COUNT=0
    FAILED_COUNT=0
    
    # Iterar sobre cada tenant
    while IFS= read -r tenant_id; do
        if backup_tenant "$tenant_id"; then
            ((SUCCESS_COUNT++))
        else
            ((FAILED_COUNT++))
        fi
        echo ""
    done <<< "$TENANTS"
    
    # Resumen
    echo "═══════════════════════════════════════════════════════════"
    echo "  📊 RESUMEN DEL BACKUP"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    echo "  Base de datos central: $([ $CENTRAL_SUCCESS -eq 1 ] && echo -e "${GREEN}✓ OK${NC}" || echo -e "${RED}✗ FALLÓ${NC}")"
    echo "  Tenants procesados: $TOTAL_TENANTS"
    echo "  Exitosos: $SUCCESS_COUNT"
    echo "  Fallidos: $FAILED_COUNT"
    echo ""
    
    # Espacio usado
    TOTAL_SIZE=$(du -sh "$BACKUP_DIR" | cut -f1)
    echo "  Espacio total usado: $TOTAL_SIZE"
    echo ""
    
    log_success "Proceso de backup completado"
    echo "  Log guardado en: $LOG_FILE"
    echo ""
    echo "═══════════════════════════════════════════════════════════"
fi

exit 0
