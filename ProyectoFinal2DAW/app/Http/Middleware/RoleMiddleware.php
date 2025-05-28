<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacade;

class RoleMiddleware{
    public function handle(Request $request, Closure $next, ...$roles){
        $user = AuthFacade::user();

        // Asegurarse de que el user esté autenticado y su rol esté entre los permitidos
        if (!$user || !in_array($user->rol, $roles)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}
