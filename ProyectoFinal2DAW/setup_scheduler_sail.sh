#!/bin/bash

# Script para configurar el scheduler de Laravel dentro del contenedor Sail
# Este script debe ejecutarse DENTRO del contenedor Laravel Sail

echo "🔧 CONFIGURANDO SCHEDULER DE LARAVEL EN SAIL"
echo "============================================="
echo ""

# Verificar si estamos dentro del contenedor
if [ ! -f /etc/debian_version ] && [ ! -f /etc/alpine-release ]; then
    echo "⚠️  Este script debe ejecutarse DENTRO del contenedor Sail"
    echo "   Ejecuta: ./vendor/bin/sail shell"
    echo "   Y luego: bash setup_scheduler_sail.sh"
    exit 1
fi

echo "✅ Ejecutando dentro del contenedor"
echo ""

# Instalar cron si no está instalado
if ! command -v cron &> /dev/null; then
    echo "📦 Instalando cron..."
    apt-get update -qq
    apt-get install -y cron
    echo "✅ Cron instalado"
else
    echo "✅ Cron ya está instalado"
fi
echo ""

# Agregar el cron job
echo "⚙️  Configurando cron job para Laravel Scheduler..."

# Crear el cron job
CRON_JOB="* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1"

# Verificar si ya existe
if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo "✅ Cron job ya existe"
else
    # Agregar el cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "✅ Cron job agregado"
fi
echo ""

# Iniciar el servicio cron
echo "🚀 Iniciando servicio cron..."
service cron start
echo ""

# Verificar que está corriendo
if service cron status | grep -q "running"; then
    echo "✅ Cron está corriendo correctamente"
else
    echo "⚠️  Cron no se inició correctamente"
fi
echo ""

# Mostrar el cron configurado
echo "📋 Cron jobs configurados:"
crontab -l
echo ""

echo "✅ CONFIGURACIÓN COMPLETADA"
echo ""
echo "El scheduler ahora se ejecutará cada minuto."
echo "Los recordatorios se enviarán diariamente a las 10:00 AM."
echo ""
echo "Para verificar:"
echo "  - Ver logs: tail -f storage/logs/laravel.log"
echo "  - Probar manualmente: php artisan citas:enviar-recordatorios"
echo "  - Ver scheduler: php artisan schedule:list"
