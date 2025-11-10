#!/bin/bash

################################################################################
# Script: cleanup-old-backups.sh
# Descripción: Elimina backups antiguos manteniendo solo los N más recientes
# Autor: Sistema Multi-Tenancy
# Versión: 1.0
################################################################################

set -e

# ============================================================================
# CONFIGURACIÓN
# ============================================================================

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="$PROJECT_DIR/storage/backups"

# Número de backups a mantener por tenant (por defecto)
KEEP_COUNT=5

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
    echo "  -k, --keep N             Número de backups a mantener por tenant (default: 5)"
    echo "  -t, --tenant-id ID       Limpiar solo el tenant especificado"
    echo "  -d, --dry-run            Mostrar qué se eliminaría sin hacerlo"
    echo "  -y, --yes                Confirmar automáticamente (no preguntar)"
    echo "  -h, --help               Mostrar esta ayuda"
    echo ""
    echo "Ejemplos:"
    echo "  $0 --keep 10             # Mantener 10 backups de cada tenant"
    echo "  $0 --tenant-id 1 --keep 3   # Mantener solo 3 del tenant 1"
    echo "  $0 --dry-run             # Ver qué se eliminaría"
    echo ""
}

cleanup_tenant_backups() {
    local tenant_id=$1
    local keep_count=$2
    local dry_run=$3
    
    local tenant_dir="$BACKUP_DIR/tenant_${tenant_id}"
    
    if [ ! -d "$tenant_dir" ]; then
        return 0
    fi
    
    # Obtener lista de backups ordenados por fecha (más reciente primero)
    local backups=$(find "$tenant_dir" -name "*.sql.gz" -type f -printf '%T@ %p\n' | sort -rn | cut -d' ' -f2-)
    local total_backups=$(echo "$backups" | wc -l)
    
    if [ $total_backups -le $keep_count ]; then
        log_info "Tenant $tenant_id: $total_backups backup(s) - No hay nada que eliminar"
        return 0
    fi
    
    local to_delete=$((total_backups - keep_count))
    log_warning "Tenant $tenant_id: $total_backups backup(s) encontrados - Se eliminarán $to_delete"
    
    # Obtener backups a eliminar (saltar los primeros keep_count)
    local backups_to_delete=$(echo "$backups" | tail -n $to_delete)
    
    local deleted_count=0
    local deleted_size=0
    
    while IFS= read -r backup_file; do
        if [ -f "$backup_file" ]; then
            local filename=$(basename "$backup_file")
            local size=$(stat -c%s "$backup_file")
            deleted_size=$((deleted_size + size))
            
            if [ "$dry_run" = true ]; then
                echo "  [DRY-RUN] Eliminaría: $filename ($(du -h "$backup_file" | cut -f1))"
            else
                echo "  Eliminando: $filename"
                rm -f "$backup_file"
                
                # Eliminar también el archivo .meta si existe
                local meta_file="${backup_file%.gz}.meta"
                [ -f "$meta_file" ] && rm -f "$meta_file"
                
                ((deleted_count++))
            fi
        fi
    done <<< "$backups_to_delete"
    
    if [ "$dry_run" = false ]; then
        local size_freed=$(numfmt --to=iec $deleted_size)
        log_success "Tenant $tenant_id: $deleted_count backup(s) eliminados - $size_freed liberados"
    fi
}

cleanup_central_backups() {
    local keep_count=$1
    local dry_run=$2
    
    local central_dir="$BACKUP_DIR/central"
    
    if [ ! -d "$central_dir" ]; then
        return 0
    fi
    
    local backups=$(find "$central_dir" -name "*.sql.gz" -type f -printf '%T@ %p\n' | sort -rn | cut -d' ' -f2-)
    local total_backups=$(echo "$backups" | wc -l)
    
    if [ $total_backups -le $keep_count ]; then
        log_info "BD Central: $total_backups backup(s) - No hay nada que eliminar"
        return 0
    fi
    
    local to_delete=$((total_backups - keep_count))
    log_warning "BD Central: $total_backups backup(s) encontrados - Se eliminarán $to_delete"
    
    local backups_to_delete=$(echo "$backups" | tail -n $to_delete)
    local deleted_count=0
    
    while IFS= read -r backup_file; do
        if [ -f "$backup_file" ]; then
            local filename=$(basename "$backup_file")
            
            if [ "$dry_run" = true ]; then
                echo "  [DRY-RUN] Eliminaría: $filename"
            else
                echo "  Eliminando: $filename"
                rm -f "$backup_file"
                ((deleted_count++))
            fi
        fi
    done <<< "$backups_to_delete"
    
    if [ "$dry_run" = false ]; then
        log_success "BD Central: $deleted_count backup(s) eliminados"
    fi
}

# ============================================================================
# MAIN
# ============================================================================

TENANT_ID=""
DRY_RUN=false
AUTO_CONFIRM=false

# Parsear argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        -k|--keep)
            KEEP_COUNT="$2"
            shift 2
            ;;
        -t|--tenant-id)
            TENANT_ID="$2"
            shift 2
            ;;
        -d|--dry-run)
            DRY_RUN=true
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
echo "  🧹 LIMPIEZA DE BACKUPS - Sistema Multi-Tenancy"
echo "═══════════════════════════════════════════════════════════"
echo ""

if [ "$DRY_RUN" = true ]; then
    log_warning "MODO DRY-RUN: No se eliminará nada, solo se mostrará"
    echo ""
fi

log_info "Manteniendo los $KEEP_COUNT backups más recientes"
log_info "Directorio: $BACKUP_DIR"
echo ""

# Confirmar si no es dry-run
if [ "$DRY_RUN" = false ] && [ "$AUTO_CONFIRM" = false ]; then
    log_warning "Esta acción eliminará backups antiguos"
    read -p "¿Desea continuar? (s/N): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        log_info "Operación cancelada"
        exit 0
    fi
    echo ""
fi

if [ -n "$TENANT_ID" ]; then
    # Limpiar solo un tenant específico
    log_info "Limpiando backups del tenant $TENANT_ID..."
    cleanup_tenant_backups "$TENANT_ID" "$KEEP_COUNT" "$DRY_RUN"
else
    # Limpiar todos los tenants
    log_info "Limpiando backups de todos los tenants..."
    echo ""
    
    for tenant_dir in "$BACKUP_DIR"/tenant_*; do
        if [ -d "$tenant_dir" ]; then
            local tenant_id=$(basename "$tenant_dir" | sed 's/tenant_//')
            cleanup_tenant_backups "$tenant_id" "$KEEP_COUNT" "$DRY_RUN"
        fi
    done
    
    echo ""
    log_info "Limpiando backups de BD central..."
    cleanup_central_backups "$KEEP_COUNT" "$DRY_RUN"
fi

echo ""
if [ "$DRY_RUN" = true ]; then
    log_info "Limpieza simulada completada"
    log_info "Ejecute sin --dry-run para eliminar realmente"
else
    log_success "Limpieza completada"
fi

echo ""
echo "═══════════════════════════════════════════════════════════"
exit 0
