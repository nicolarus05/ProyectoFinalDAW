<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            // Rutas centrales (sin tenant)
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
            
            // Rutas de tenant (middleware 'web' aplicado dentro de tenant.php)
            Route::group([], function () {
                require base_path('routes/tenant.php');
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // El middleware 'web' incluye StartSession, VerifyCsrfToken, etc.
        
        // CSRF habilitado para todas las rutas (seguridad)
        
        // Agregar CORS headers a los assets estáticos para multi-tenancy
        $middleware->append(\App\Http\Middleware\AddCorsHeadersToAssets::class);
        
        // Rate Limiting - Protección contra fuerza bruta
        $middleware->throttleApi();
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'desktop.only' => \App\Http\Middleware\CheckDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Personalizar respuesta de Rate Limiting
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Demasiadas peticiones. Por favor, espera un momento.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60
                ], 429);
            }

            return response()->view('errors.429', [
                'retry_after' => $e->getHeaders()['Retry-After'] ?? 60
            ], 429);
        });
    })->create();

// Registrar alias de middleware personalizado
$app->router->aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);

return $app;
