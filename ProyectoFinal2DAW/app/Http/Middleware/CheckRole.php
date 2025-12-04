<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) return redirect('/login');

        $user = Auth::user();
        
        // Si se pasa una cadena con roles separados por coma, dividirla
        $allowedRoles = [];
        foreach ($roles as $role) {
            if (str_contains($role, ',')) {
                $allowedRoles = array_merge($allowedRoles, explode(',', $role));
            } else {
                $allowedRoles[] = $role;
            }
        }
        
        // Limpiar espacios en blanco
        $allowedRoles = array_map('trim', $allowedRoles);
        
        // Log temporal para debugging
        Log::info('CheckRole - Usuario: ' . $user->email . ' | Rol usuario: ' . $user->rol . ' | Roles permitidos: ' . json_encode($allowedRoles));
        
        if (!in_array($user->rol, $allowedRoles)) {
            Log::warning('CheckRole - Acceso denegado para usuario: ' . $user->email . ' con rol: ' . $user->rol);
            abort(403, 'Acceso no autorizado.');
        }

        return $next($request);
    }
}

