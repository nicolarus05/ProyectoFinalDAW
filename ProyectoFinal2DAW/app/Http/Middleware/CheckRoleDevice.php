<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // La política de dispositivo solo afecta a las cuentas de empleado.
        // Gerentes, administradores y clientes conservan el acceso móvil.
        if (! $user || $user->rol !== 'empleado') {
            return $next($request);
        }

        $agent = new Agent;
        $agent->setUserAgent($request->userAgent());

        if ($agent->isDesktop()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta de empleado solo puede acceder desde un ordenador.',
            ], 403);
        }

        return response()->view('errors.device-restricted', status: 403);
    }
}
