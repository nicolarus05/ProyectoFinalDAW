<?php
/**
 * Queue Worker Daemon para Plesk
 * Este script mantiene el queue worker corriendo continuamente
 */

// Configuración
$projectPath = '/var/www/vhosts/tu-dominio.com/httpdocs'; // AJUSTA ESTA RUTA
$logFile = $projectPath . '/storage/logs/queue-worker.log';
$maxRunTime = 3600; // 1 hora, después se reinicia automáticamente

// Cambiar al directorio del proyecto
chdir($projectPath);

// Log de inicio
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Queue worker iniciado\n", FILE_APPEND);

// Ejecutar el queue worker
$cmd = "php artisan queue:work --sleep=3 --tries=3 --max-time={$maxRunTime} 2>&1";

echo "Iniciando queue worker...\n";
echo "Ruta: {$projectPath}\n";
echo "Log: {$logFile}\n";
echo "Comando: {$cmd}\n\n";

// Ejecutar y capturar salida
exec($cmd, $output, $returnVar);

// Log de finalización
$status = $returnVar === 0 ? 'OK' : 'ERROR';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Worker finalizado. Estado: {$status}\n", FILE_APPEND);

// Si hubo error, registrarlo
if ($returnVar !== 0) {
    file_put_contents($logFile, "Salida:\n" . implode("\n", $output) . "\n", FILE_APPEND);
}
