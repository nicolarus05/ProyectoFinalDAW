#!/bin/bash

# Script para iniciar el Queue Worker de Laravel en Sail
# Este worker procesará los emails encolados automáticamente

echo "🚀 INICIANDO QUEUE WORKER DE LARAVEL"
echo "====================================="
echo ""

# Verificar que Sail está corriendo
if ! docker ps | grep -q "laravel.test"; then
    echo "❌ Laravel Sail no está corriendo"
    echo "   Ejecuta: ./vendor/bin/sail up -d"
    exit 1
fi

echo "✅ Sail está corriendo"
echo ""

echo "📧 Iniciando queue worker para procesar emails..."
echo "   - Esto procesará automáticamente los emails encolados"
echo "   - Los emails se enviarán inmediatamente cuando se creen citas"
echo "   - Presiona Ctrl+C para detener"
echo ""

# Iniciar el queue worker
./vendor/bin/sail artisan queue:work --verbose --tries=3 --timeout=60

