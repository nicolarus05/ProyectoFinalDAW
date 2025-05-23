<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles){
        $user = $request->user();

        // Asegurarse de que el usuario esté autenticado y su rol esté entre los permitidos
        if (!$user || !in_array($user->rol, $roles)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
