<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermisoAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $modulo, $accion)
    {
        $admin = auth()->guard('admin')->user();
    
        if (!$admin || !$admin->tienePermiso($modulo, $accion)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }
    
        return $next($request);
    }
}
