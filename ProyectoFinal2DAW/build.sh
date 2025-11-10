#!/usr/bin/env bash
# Build Script para Render
# Este script se ejecuta durante el despliegue

set -e # Exit on error

echo "🚀 Iniciando build para producción..."

# 1. Instalar dependencias de Composer (sin dev)
echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 2. Generar APP_KEY si no existe
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generando APP_KEY..."
    php artisan key:generate --force
fi

# 3. Limpiar cachés anteriores
echo "🧹 Limpiando cachés..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 4. Optimizar configuración para producción
echo "⚡ Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Ejecutar migraciones en base de datos central
echo "🗄️  Ejecutando migraciones de base de datos central..."
php artisan migrate --force --database=landlord

# 6. Crear directorio de storage si no existe
echo "📁 Configurando directorios de storage..."
mkdir -p storage/app/public
mkdir -p storage/app/tenants
mkdir -p storage/backups
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# 7. Establecer permisos correctos
echo "🔐 Estableciendo permisos..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 8. Ejecutar migraciones de tenants existentes
echo "🏢 Migrando tenants existentes..."
php artisan tenants:migrate --force || echo "⚠️  No hay tenants para migrar (primera instalación)"

echo "✅ Build completado exitosamente!"
