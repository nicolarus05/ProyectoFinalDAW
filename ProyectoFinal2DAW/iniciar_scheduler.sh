#!/bin/bash

# Script MAESTRO para iniciar el scheduler en Docker Sail
# Ejecutar FUERA del contenedor

echo "🚀 CONFIGURACIÓN AUTOMÁTICA DEL SCHEDULER"
echo "=========================================="
echo ""

# Verificar que Sail está corriendo
if ! docker ps | grep -q "laravel.test"; then
    echo "❌ Laravel Sail no está corriendo"
    echo "   Inicia Sail con: ./vendor/bin/sail up -d"
    exit 1
fi

echo "✅ Sail está corriendo"
echo ""

# Copiar el script al contenedor y ejecutarlo
echo "📋 Configurando scheduler dentro del contenedor..."
./vendor/bin/sail exec laravel.test bash -c "cd /var/www/html && bash setup_scheduler_sail.sh"

echo ""
echo "✅ CONFIGURACIÓN COMPLETADA"
echo ""
echo "📌 Próximos pasos:"
echo "   1. Crea una cita para mañana (desde la app web)"
echo "   2. Espera a las 10:00 AM o ejecuta manualmente:"
echo "      ./vendor/bin/sail artisan citas:enviar-recordatorios"
echo "   3. Verifica los logs:"
echo "      ./vendor/bin/sail exec laravel.test tail -f storage/logs/laravel.log"
