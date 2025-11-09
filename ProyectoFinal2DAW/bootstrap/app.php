<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Registrar rutas de tenant con middleware automático
            Route::middleware([
                'web',
                Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
                Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // El middleware 'web' ya incluye StartSession por defecto
        // Se ejecuta automáticamente en todas las rutas web
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Registrar alias de middleware personalizado
$app->router->aliasMiddleware('role', \App\Http\Middleware\CheckRole::class);

return $app;
