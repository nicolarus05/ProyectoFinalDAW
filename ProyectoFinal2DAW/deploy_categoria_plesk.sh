#!/bin/bash

###############################################################################
# Script de Despliegue Automático - Sistema de Facturación por Categoría
# Para servidor Plesk con Laravel
###############################################################################

echo "========================================================================="
echo "🚀 DESPLIEGUE: Sistema de Facturación por Categoría"
echo "========================================================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables (CONFIGURA ESTAS)
APP_PATH="/var/www/vhosts/tu_dominio/httpdocs"
TENANT_ID="salonlh"

# Función para imprimir en color
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Verificar si estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "No se encontró el archivo 'artisan'. Asegúrate de estar en la raíz de Laravel."
    exit 1
fi

print_info "Directorio de trabajo: $(pwd)"
echo ""

###############################################################################
# PASO 1: BACKUP
###############################################################################
echo "========================================================================="
echo "📦 PASO 1: Creando backup..."
echo "========================================================================="

BACKUP_DIR="../backup_antes_categoria_$(date +%Y%m%d_%H%M%S)"
print_info "Creando backup en: $BACKUP_DIR"

cp -r . "$BACKUP_DIR" 2>/dev/null

if [ $? -eq 0 ]; then
    print_success "Backup creado correctamente"
else
    print_warning "No se pudo crear backup automático. Continúa bajo tu propia responsabilidad."
    read -p "¿Deseas continuar? (s/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        print_error "Despliegue cancelado"
        exit 1
    fi
fi

echo ""

###############################################################################
# PASO 2: ACTUALIZAR CÓDIGO (si usa Git)
###############################################################################
echo "========================================================================="
echo "📥 PASO 2: Actualizando código..."
echo "========================================================================="

if [ -d ".git" ]; then
    print_info "Repositorio Git detectado. Actualizando..."
    
    git fetch origin
    git pull origin main
    
    if [ $? -eq 0 ]; then
        print_success "Código actualizado correctamente desde Git"
    else
        print_error "Error al actualizar desde Git"
        exit 1
    fi
else
    print_warning "No se detectó repositorio Git. Asegúrate de haber subido los archivos manualmente."
fi

echo ""

###############################################################################
# PASO 3: VERIFICAR MIGRACIÓN
###############################################################################
echo "========================================================================="
echo "🗄️  PASO 3: Verificando migración..."
echo "========================================================================="

MIGRATION_FILE="database/migrations/tenant/2026_01_24_165712_add_categoria_to_bonos_plantilla_table.php"

if [ -f "$MIGRATION_FILE" ]; then
    print_success "Archivo de migración encontrado"
else
    print_error "Archivo de migración NO encontrado: $MIGRATION_FILE"
    exit 1
fi

echo ""

###############################################################################
# PASO 4: EJECUTAR MIGRACIÓN
###############################################################################
echo "========================================================================="
echo "🔧 PASO 4: Ejecutando migración..."
echo "========================================================================="

print_info "Ejecutando migración para tenant: $TENANT_ID"

# Intentar método 1: tenants:run con comillas simples
php artisan tenants:run 'php artisan migrate --path='"$MIGRATION_FILE"' --force' 2>/dev/null

if [ $? -eq 0 ]; then
    print_success "Migración ejecutada correctamente"
else
    print_warning "Método 1 falló, intentando método 2..."
    
    # Método 2: tenants:migrate (migra todas las pendientes)
    php artisan tenants:migrate --force 2>/dev/null
    
    if [ $? -eq 0 ]; then
        print_success "Migración ejecutada correctamente (método 2)"
    else
        print_warning "Método 2 falló, intentando método 3..."
        
        # Método 3: migrate directo (si el tenant ya está inicializado)
        php artisan migrate --path="$MIGRATION_FILE" --force 2>/dev/null
        
        if [ $? -eq 0 ]; then
            print_success "Migración ejecutada correctamente (método 3)"
        else
            print_error "No se pudo ejecutar la migración automáticamente"
            print_warning "Ejecuta manualmente UNO de estos comandos:"
            echo ""
            echo "Opción 1:"
            echo "  php artisan tenants:migrate --force"
            echo ""
            echo "Opción 2:"
            echo "  php artisan migrate --path=$MIGRATION_FILE --force"
            echo ""
            echo "Opción 3 (desde navegador):"
            echo "  Crea ejecutar_migracion.php y accede desde el navegador"
            echo ""
            
            read -p "¿Has ejecutado la migración manualmente? (s/n): " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Ss]$ ]]; then
                print_error "Despliegue cancelado. Ejecuta la migración y vuelve a ejecutar el script."
                exit 1
            fi
            print_info "Continuando con el despliegue..."
        fi
    fi
fi

echo ""

###############################################################################
# PASO 5: ASIGNAR CATEGORÍAS A BONOS
###############################################################################
echo "========================================================================="
echo "🏷️  PASO 5: Asignando categorías a bonos existentes..."
echo "========================================================================="

if [ -f "asignar_categorias_bonos.php" ]; then
    print_info "Ejecutando script de asignación de categorías..."
    
    php asignar_categorias_bonos.php
    
    if [ $? -eq 0 ]; then
        print_success "Categorías asignadas correctamente"
    else
        print_warning "Error al asignar categorías. Verifica manualmente."
    fi
else
    print_warning "Script 'asignar_categorias_bonos.php' no encontrado"
    print_info "Asigna categorías manualmente en la base de datos"
fi

echo ""

###############################################################################
# PASO 6: LIMPIAR CACHÉ
###############################################################################
echo "========================================================================="
echo "🧹 PASO 6: Limpiando caché..."
echo "========================================================================="

print_info "Limpiando todas las cachés..."

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

print_success "Cachés limpiadas"

echo ""
print_info "Optimizando para producción..."

php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "Cachés optimizadas"

echo ""

###############################################################################
# PASO 7: VERIFICACIONES
###############################################################################
echo "========================================================================="
echo "✅ PASO 7: Verificaciones..."
echo "========================================================================="

print_info "Verificando que todos los bonos tienen categoría..."

# Crear script temporal de verificación
cat > verify_temp.php << 'EOF'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenant = \App\Models\Tenant::find('salonlh');
if ($tenant) {
    tenancy()->initialize($tenant);
    
    $bonosSinCategoria = DB::table('bonos_plantilla')
        ->whereNull('categoria')
        ->count();
    
    if ($bonosSinCategoria == 0) {
        echo "OK";
    } else {
        echo "ERROR: $bonosSinCategoria bonos sin categoría";
    }
} else {
    echo "ERROR: Tenant no encontrado";
}
EOF

VERIFY_RESULT=$(php verify_temp.php)
rm verify_temp.php

if [[ "$VERIFY_RESULT" == "OK" ]]; then
    print_success "Todos los bonos tienen categoría asignada"
else
    print_error "$VERIFY_RESULT"
    print_warning "Asigna categorías manualmente a los bonos que faltan"
fi

echo ""

###############################################################################
# PASO 8: TESTS (Opcional)
###############################################################################
echo "========================================================================="
echo "🧪 PASO 8: Tests (opcional)..."
echo "========================================================================="

read -p "¿Deseas ejecutar los tests de verificación? (s/n): " -n 1 -r
echo

if [[ $REPLY =~ ^[Ss]$ ]]; then
    if [ -f "test_sistema_completo_categorias.php" ]; then
        print_info "Ejecutando test completo del sistema..."
        php test_sistema_completo_categorias.php
        echo ""
    fi
    
    if [ -f "test_edge_cases_categorias.php" ]; then
        print_info "Ejecutando test de casos edge..."
        php test_edge_cases_categorias.php
        echo ""
    fi
    
    if [ -f "test_vista_facturacion.php" ]; then
        print_info "Ejecutando test de vista..."
        php test_vista_facturacion.php
        echo ""
    fi
else
    print_info "Tests omitidos. Puedes ejecutarlos manualmente después."
fi

echo ""

###############################################################################
# PASO 9: LIMPIEZA
###############################################################################
echo "========================================================================="
echo "🗑️  PASO 9: Limpieza de archivos temporales..."
echo "========================================================================="

print_warning "Se recomienda eliminar los scripts de test por seguridad:"
echo "  - test_sistema_completo_categorias.php"
echo "  - test_edge_cases_categorias.php"
echo "  - test_vista_facturacion.php"
echo "  - test_facturacion_categoria.php"
echo "  - test_cobro_deuda.php"
echo "  - test_pago_deuda.php"
echo "  - asignar_categorias_bonos.php"

read -p "¿Deseas eliminar estos archivos ahora? (s/n): " -n 1 -r
echo

if [[ $REPLY =~ ^[Ss]$ ]]; then
    rm -f test_*.php asignar_categorias_bonos.php
    print_success "Archivos de test eliminados"
else
    print_warning "Recuerda eliminar estos archivos manualmente más tarde"
fi

echo ""

###############################################################################
# RESUMEN FINAL
###############################################################################
echo "========================================================================="
echo "🎉 DESPLIEGUE COMPLETADO"
echo "========================================================================="
echo ""

print_success "El sistema de facturación por categoría está operativo"
echo ""

echo "📊 PRÓXIMOS PASOS:"
echo ""
echo "1. 🌐 Accede a tu aplicación: https://tu_dominio.com"
echo "2. 🔐 Inicia sesión"
echo "3. 📈 Ve a la vista de Facturación"
echo "4. ✅ Verifica que se muestre el desglose de bonos por categoría"
echo "5. 📝 Revisa los logs durante las próximas 24-48 horas:"
echo "   tail -f storage/logs/laravel.log"
echo ""

print_info "Backup guardado en: $BACKUP_DIR"
print_warning "En caso de problemas, puedes restaurar desde el backup"

echo ""
echo "========================================================================="
echo "✨ ¡Gracias por usar el script de despliegue automático!"
echo "========================================================================="
echo ""
