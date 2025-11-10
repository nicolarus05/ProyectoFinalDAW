#!/bin/bash

################################################################################
# Script: restore-tenant.sh
# Descripción: Restaura backup de un tenant específico
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

# Configuración de base de datos
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_DIR/.env"
BACKUP_DIR="$PROJECT_DIR/storage/backups"

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

# ============================================================================
# FUNCIONES
# ============================================================================

log_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

log_error() {
    echo -e "${RED}✗ $1${NC}"
}

log_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

show_usage() {
    echo ""
    echo "Uso: $0 [OPCIONES]"
    echo ""
    echo "Opciones:"
    echo "  -t, --tenant-id ID       ID del tenant a restaurar (requerido)"
    echo "  -f, --file ARCHIVO       Archivo de backup específico (.sql.gz)"
    echo "  -l, --list               Listar backups disponibles"
    echo "  -L, --latest             Usar el backup más reciente"
    echo "  -c, --create-tenant      Crear entrada del tenant en BD central si no existe"
    echo "  -d, --drop-database      Eliminar BD existente antes de restaurar (¡PELIGROSO!)"
    echo "  -y, --yes                Confirmar automáticamente (no preguntar)"
    echo "  -h, --help               Mostrar esta ayuda"
    echo ""
    echo "Ejemplos:"
    echo "  $0 --tenant-id 1 --latest"
    echo "  $0 -t 1 -f tenant_1/1_salon-belleza_20241110_143022.sql.gz"
    echo "  $0 --list"
    echo ""
}

# Listar backups disponibles
list_backups() {
    local tenant_id=$1
    
    echo ""
    echo "═══════════════════════════════════════════════════════════"
    echo "  📦 BACKUPS DISPONIBLES"
    echo "═══════════════════════════════════════════════════════════"
    echo ""
    
    if [ -n "$tenant_id" ]; then
        # Listar backups de un tenant específico
        local tenant_dir="$BACKUP_DIR/tenant_${tenant_id}"
        if [ ! -d "$tenant_dir" ]; then
            log_warning "No hay backups para el tenant $tenant_id"
            return 1
        fi
        
        log_info "Backups del tenant $tenant_id:"
        echo ""
        
        local backups=$(find "$tenant_dir" -name "*.sql.gz" -type f | sort -r)
        if [ -z "$backups" ]; then
            log_warning "No se encontraron backups"
            return 1
        fi
        
        local count=1
        while IFS= read -r backup_file; do
            local filename=$(basename "$backup_file")
            local size=$(du -h "$backup_file" | cut -f1)
            local date=$(stat -c %y "$backup_file" | cut -d'.' -f1)
            
            echo "  [$count] $filename"
            echo "      Tamaño: $size"
            echo "      Fecha: $date"
            
            # Leer metadata si existe
            local meta_file="${backup_file%.gz}.meta"
            if [ -f "$meta_file" ]; then
                local tenant_nombre=$(grep "^tenant_nombre=" "$meta_file" | cut -d'=' -f2-)
                [ -n "$tenant_nombre" ] && echo "      Tenant: $tenant_nombre"
            fi
            echo ""
            
            ((count++))
        done <<< "$backups"
    else
        # Listar todos los backups
        log_info "Todos los backups por tenant:"
        echo ""
        
        for tenant_dir in "$BACKUP_DIR"/tenant_*; do
            if [ ! -d "$tenant_dir" ]; then
                continue
            fi
            
            local tenant_id=$(basename "$tenant_dir" | sed 's/tenant_//')
            local backup_count=$(find "$tenant_dir" -name "*.sql.gz" -type f | wc -l)
            local total_size=$(du -sh "$tenant_dir" | cut -f1)
            
            echo "  Tenant $tenant_id: $backup_count backup(s) - $total_size"
        done
        
        echo ""
        log_info "Use --tenant-id ID para ver detalles de un tenant específico"
    fi
    
    echo ""
}

# Obtener el backup más reciente de un tenant
get_latest_backup() {
    local tenant_id=$1
    local tenant_dir="$BACKUP_DIR/tenant_${tenant_id}"
    
    if [ ! -d "$tenant_dir" ]; then
        return 1
    fi
    
    local latest=$(find "$tenant_dir" -name "*.sql.gz" -type f | sort -r | head -n1)
    echo "$latest"
}

# Confirmar acción
confirm_action() {
    local message=$1
    
    if [ "$AUTO_CONFIRM" = true ]; then
        return 0
    fi
    
    echo ""
    log_warning "$message"
    read -p "¿Desea continuar? (s/N): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        log_info "Operación cancelada por el usuario"
        exit 0
    fi
}

# Restaurar tenant
restore_tenant() {
    local tenant_id=$1
    local backup_file=$2
    local create_tenant=$3
    local drop_database=$4
    
    if [ ! -f "$backup_file" ]; then
        log_error "Archivo de backup no encontrado: $backup_file"
        exit 1
    fi
    
    log_info "Restaurando tenant $tenant_id desde: $(basename "$backup_file")"
    
    # Leer metadata
    local meta_file="${backup_file%.gz}.meta"
    if [ -f "$meta_file" ]; then
        local tenant_nombre=$(grep "^tenant_nombre=" "$meta_file" | cut -d'=' -f2-)
        local tenant_slug=$(grep "^tenant_slug=" "$meta_file" | cut -d'=' -f2-)
        local backup_date=$(grep "^date=" "$meta_file" | cut -d'=' -f2-)
        
        echo ""
        log_info "Información del backup:"
        [ -n "$tenant_nombre" ] && echo "  Nombre: $tenant_nombre"
        [ -n "$tenant_slug" ] && echo "  Slug: $tenant_slug"
        [ -n "$backup_date" ] && echo "  Fecha: $backup_date"
    fi
    
    local tenant_db="tenant_${tenant_id}"
    
    # Verificar si el tenant existe en la BD central
    local tenant_exists=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -N -e "SELECT COUNT(*) FROM tenants WHERE id='$tenant_id';" 2>/dev/null)
    
    if [ "$tenant_exists" = "0" ]; then
        if [ "$create_tenant" = true ]; then
            log_warning "El tenant no existe en la BD central"
            
            if [ -f "$meta_file" ]; then
                confirm_action "Se creará el tenant en la BD central con la información del backup"
                
                log_info "Creando tenant en BD central..."
                mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" <<EOF
INSERT INTO tenants (id, slug, nombre, created_at, updated_at)
VALUES ('$tenant_id', '$tenant_slug', '$tenant_nombre', NOW(), NOW());
EOF
                log_success "Tenant creado en BD central"
            else
                log_error "No hay metadata disponible para crear el tenant"
                exit 1
            fi
        else
            log_error "El tenant $tenant_id no existe en la BD central"
            log_info "Use --create-tenant para crearlo automáticamente"
            exit 1
        fi
    fi
    
    # Verificar si la BD del tenant existe
    local db_exists=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SHOW DATABASES LIKE '$tenant_db';" 2>/dev/null | grep "$tenant_db" || echo "")
    
    if [ -n "$db_exists" ]; then
        if [ "$drop_database" = true ]; then
            confirm_action "¡ATENCIÓN! Se eliminará la base de datos $tenant_db existente. Esta acción NO se puede deshacer."
            
            log_warning "Eliminando base de datos existente..."
            mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "DROP DATABASE $tenant_db;" 2>/dev/null
            log_success "Base de datos eliminada"
        else
            log_error "La base de datos $tenant_db ya existe"
            log_info "Use --drop-database para eliminarla antes de restaurar (¡PELIGROSO!)"
            exit 1
        fi
    fi
    
    # Descomprimir y restaurar
    log_info "Descomprimiendo backup..."
    local temp_sql="/tmp/restore_tenant_${tenant_id}_$$.sql"
    
    if gunzip -c "$backup_file" > "$temp_sql" 2>/dev/null; then
        log_success "Backup descomprimido"
        
        log_info "Restaurando base de datos..."
        if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" < "$temp_sql" 2>/dev/null; then
            log_success "Base de datos restaurada exitosamente"
            
            # Limpiar archivo temporal
            rm -f "$temp_sql"
            
            echo ""
            log_success "Restauración completada"
            log_info "Tenant $tenant_id restaurado desde: $(basename "$backup_file")"
            
            return 0
        else
            log_error "Error al restaurar la base de datos"
            rm -f "$temp_sql"
            exit 1
        fi
    else
        log_error "Error al descomprimir el backup"
        rm -f "$temp_sql"
        exit 1
    fi
}

# ============================================================================
# MAIN
# ============================================================================

# Variables
TENANT_ID=""
BACKUP_FILE=""
LIST_MODE=false
USE_LATEST=false
CREATE_TENANT=false
DROP_DATABASE=false
AUTO_CONFIRM=false

# Parsear argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        -t|--tenant-id)
            TENANT_ID="$2"
            shift 2
            ;;
        -f|--file)
            BACKUP_FILE="$2"
            shift 2
            ;;
        -l|--list)
            LIST_MODE=true
            shift
            ;;
        -L|--latest)
            USE_LATEST=true
            shift
            ;;
        -c|--create-tenant)
            CREATE_TENANT=true
            shift
            ;;
        -d|--drop-database)
            DROP_DATABASE=true
            shift
            ;;
        -y|--yes)
            AUTO_CONFIRM=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            log_error "Opción desconocida: $1"
            show_usage
            exit 1
            ;;
    esac
done

echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  🔄 RESTORE MULTI-TENANCY - Sistema de Salón de Belleza"
echo "═══════════════════════════════════════════════════════════"

# Modo listar
if [ "$LIST_MODE" = true ]; then
    list_backups "$TENANT_ID"
    exit 0
fi

# Validar tenant ID
if [ -z "$TENANT_ID" ]; then
    log_error "Debe especificar el ID del tenant con --tenant-id"
    show_usage
    exit 1
fi

# Determinar archivo de backup
if [ "$USE_LATEST" = true ]; then
    BACKUP_FILE=$(get_latest_backup "$TENANT_ID")
    if [ -z "$BACKUP_FILE" ]; then
        log_error "No se encontraron backups para el tenant $TENANT_ID"
        exit 1
    fi
    log_info "Usando el backup más reciente: $(basename "$BACKUP_FILE")"
elif [ -z "$BACKUP_FILE" ]; then
    log_error "Debe especificar --file o --latest"
    show_usage
    exit 1
else
    # Si es ruta relativa, buscar en el directorio de backups del tenant
    if [[ "$BACKUP_FILE" != /* ]]; then
        BACKUP_FILE="$BACKUP_DIR/$BACKUP_FILE"
    fi
fi

# Verificar conexión a MySQL
log_info "Verificando conexión a MySQL..."
if ! mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" >/dev/null 2>&1; then
    log_error "No se puede conectar a MySQL. Verifica las credenciales."
    exit 1
fi
log_success "Conexión establecida"

# Restaurar tenant
restore_tenant "$TENANT_ID" "$BACKUP_FILE" "$CREATE_TENANT" "$DROP_DATABASE"

echo ""
echo "═══════════════════════════════════════════════════════════"
exit 0
