<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint para monitoreo (Render, etc.)
     *
     * @return JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        try {
            // Verificar conexión a base de datos
            DB::connection()->getPdo();
            $dbStatus = 'connected';
            $healthy = true;
        } catch (\Throwable $e) {
            $dbStatus = 'failed';
            $healthy = false;
        }

        $response = [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toDateTimeString(),
            'checks' => [
                'database' => $dbStatus,
                'app' => 'running',
            ],
        ];

        // En desarrollo, incluir más detalles
        if (app()->environment('local')) {
            $response['environment'] = 'local';
            $response['app_name'] = config('app.name');
        }

        return response()->json(
            $response,
            $healthy ? 200 : 503
        );
    }
}
