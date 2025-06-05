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
    public function handle(Request $request, Closure $next, $modulo, $accion)
    {
        $admin = auth('admin')->user();

        if (!$admin || !$admin->tienePermiso($modulo, $accion)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        return $next($request);
    }
}
