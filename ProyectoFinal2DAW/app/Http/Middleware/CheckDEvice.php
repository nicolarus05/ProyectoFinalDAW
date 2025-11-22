<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class CheckDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent = new Agent();

        // Si es móvil o tablet, bloqueamos el acceso
        if ($agent->isMobile() || $agent->isTablet()) {
            
            // Si la petición espera JSON (AJAX), devolvemos error JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '⚠️ Acción no permitida desde dispositivos móviles. Por favor, usa un ordenador.'
                ], 403);
            }

            // Si es una petición normal, redirigimos con mensaje de error
            return redirect()->back()->with('error', '⚠️ Esta acción solo está disponible desde un ordenador.');
        }

        return $next($request);
    }
}