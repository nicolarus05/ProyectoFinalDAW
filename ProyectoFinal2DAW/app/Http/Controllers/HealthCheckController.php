<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint para monitoreo (Render, etc.)
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $healthy = true;
        $checks = [];

        // 1. Verificar conexión a base de datos
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful',
            ];
        } catch (\Throwable $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
            $healthy = false;
        }

        // 2. Verificar sistema de caché
        try {
            $cacheKey = 'health_check_' . time();
            $cacheValue = 'test_' . rand(1000, 9999);
            
            Cache::put($cacheKey, $cacheValue, 10);
            $retrieved = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($retrieved === $cacheValue) {
                $checks['cache'] = [
                    'status' => 'healthy',
                    'message' => 'Cache read/write successful',
                    'driver' => config('cache.default'),
                ];
            } else {
                throw new \Exception('Cache value mismatch');
            }
        } catch (\Throwable $e) {
            $checks['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache system failed: ' . $e->getMessage(),
                'driver' => config('cache.default'),
            ];
            $healthy = false;
        }

        // 3. Verificar espacio en disco
        try {
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

            if ($usedPercent > 90) {
                $checks['disk'] = [
                    'status' => 'warning',
                    'message' => 'Disk space critically low',
                    'used_percent' => $usedPercent,
                    'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                ];
                $healthy = false;
            } else {
                $checks['disk'] = [
                    'status' => 'healthy',
                    'message' => 'Disk space sufficient',
                    'used_percent' => $usedPercent,
                    'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                ];
            }
        } catch (\Throwable $e) {
            $checks['disk'] = [
                'status' => 'unknown',
                'message' => 'Unable to check disk space: ' . $e->getMessage(),
            ];
        }

        // 4. Verificar sistema de colas (si no es sync)
        if (config('queue.default') !== 'sync') {
            try {
                $queueSize = DB::table('jobs')->count();
                
                if ($queueSize > 1000) {
                    $checks['queue'] = [
                        'status' => 'warning',
                        'message' => 'Queue backlog is high',
                        'pending_jobs' => $queueSize,
                    ];
                } else {
                    $checks['queue'] = [
                        'status' => 'healthy',
                        'message' => 'Queue processing normally',
                        'pending_jobs' => $queueSize,
                    ];
                }
            } catch (\Throwable $e) {
                $checks['queue'] = [
                    'status' => 'unknown',
                    'message' => 'Unable to check queue: ' . $e->getMessage(),
                ];
            }
        }

        // 5. Estado de la aplicación
        $checks['app'] = [
            'status' => 'healthy',
            'message' => 'Application running',
            'version' => config('app.version', '1.0.0'),
        ];

        $response = [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        // En desarrollo, incluir más detalles
        if (app()->environment('local', 'staging')) {
            $response['debug'] = [
                'environment' => app()->environment(),
                'app_name' => config('app.name'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ];
        }

        return response()->json(
            $response,
            $healthy ? 200 : 503
        );
    }
}
