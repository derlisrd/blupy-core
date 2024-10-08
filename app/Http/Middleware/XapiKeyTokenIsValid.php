<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XapiKeyTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('x-api-key');

        if ( !$key || $key !== config('app.x_api_key')) {
            return response()->json([
                'success'=>false,
                'message'=>'Api key invalid',
            ],401);
        }
        return $next($request);
    }
}
