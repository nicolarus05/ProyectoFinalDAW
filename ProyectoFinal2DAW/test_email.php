<?php

// Test de conexión SMTP
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "🧪 TEST DE ENVÍO DE EMAIL\n";
echo "========================\n\n";

$email = 'valderon531@gmail.com';

try {
    echo "📧 Enviando email a: {$email}\n";
    echo "📮 Usando SMTP: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port') . "\n";
    echo "👤 Usuario: " . config('mail.mailers.smtp.username') . "\n";
    echo "🔐 TLS: " . config('mail.mailers.smtp.encryption') . "\n\n";
    
    Mail::raw('Este es un email de prueba desde Laravel.', function($message) use ($email) {
        $message->to($email)
                ->subject('✅ Test de Email - ' . now());
    });
    
    echo "✅ Email enviado exitosamente!\n";
    echo "⏰ Hora: " . now() . "\n\n";
    echo "📝 Revisa:\n";
    echo "   1. Bandeja de entrada de {$email}\n";
    echo "   2. Carpeta de SPAM\n";
    echo "   3. Puede tardar 1-2 minutos en llegar\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR AL ENVIAR EMAIL\n\n";
    echo "Tipo: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n\n";
    echo "Posibles causas:\n";
    echo "  1. Contraseña de aplicación de Gmail incorrecta\n";
    echo "  2. Acceso bloqueado por Gmail\n";
    echo "  3. Firewall bloqueando puerto 587\n";
    echo "  4. Configuración de TLS incorrecta\n\n";
    
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
