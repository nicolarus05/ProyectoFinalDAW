#!/bin/bash

# Script para probar el sistema de recordatorios de citas
# Uso: ./test_recordatorios.sh

echo "🧪 PRUEBA DEL SISTEMA DE RECORDATORIOS"
echo "======================================"
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Verificar que Sail está corriendo
echo "1️⃣  Verificando que Docker/Sail está corriendo..."
if ! docker ps | grep -q "laravel.test"; then
    echo -e "${RED}❌ Laravel Sail no está corriendo${NC}"
    echo "   Ejecuta: ./vendor/bin/sail up -d"
    exit 1
fi
echo -e "${GREEN}✅ Sail está corriendo${NC}"
echo ""

# 2. Ver tenants disponibles
echo "2️⃣  Listando tenants disponibles..."
./vendor/bin/sail artisan tinker --execute="echo App\Models\Tenant::pluck('id')->join(', ');"
echo ""

# 3. Buscar citas para mañana
echo "3️⃣  Buscando citas programadas para mañana en todos los tenants..."
echo ""

# 4. Ejecutar comando de recordatorios
echo "4️⃣  Ejecutando comando de recordatorios..."
./vendor/bin/sail artisan citas:enviar-recordatorios
echo ""

# 5. Ver últimos logs
echo "5️⃣  Últimos logs de emails/recordatorios..."
./vendor/bin/sail exec laravel.test tail -20 storage/logs/laravel.log | grep -i "email\|recordatorio" || echo "No hay logs recientes"
echo ""

# 6. Verificar scheduler
echo "6️⃣  Verificando configuración del scheduler..."
./vendor/bin/sail artisan schedule:list | grep recordatorio || echo -e "${YELLOW}⚠️  Comando no encontrado en scheduler${NC}"
echo ""

# 7. Instrucciones para crear cita de prueba
echo "7️⃣  Para probar, crea una cita para mañana:"
echo "   - Accede a la aplicación web"
echo "   - Agenda una cita para mañana"
echo "   - Espera a las 10:00 AM o ejecuta manualmente:"
echo "   ./vendor/bin/sail artisan citas:enviar-recordatorios"
echo ""

echo -e "${GREEN}✅ Prueba completada${NC}"
