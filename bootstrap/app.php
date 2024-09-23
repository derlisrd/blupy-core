<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Auth\AuthenticationException;
use App\Http\Middleware\XapiKeyTokenIsValid;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Intervention\Image\Exceptions\NotWritableException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        //apiPrefix:'/api',
        then:function(){
            Route::prefix('bancard')
            ->middleware('x-api-key')
            ->name('bancard')
            ->group(base_path('routes/bancard.php'));

            Route::middleware('api')
            ->prefix('rest')
            ->name('rest')
            ->group(base_path('routes/rest.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            XapiKeyTokenIsValid::class,
        ]);
        $middleware->alias([
            'x-api-key' => XapiKeyTokenIsValid::class
        ]);
        //$middleware->append(\App\Http\Middleware\XapiKeyTokenIsValid::class);
        /* $middleware->use([
            \App\Http\Middleware\XapiKeyTokenIsValid::class
        ]); */
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (AuthenticationException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'Sesión inválida. Inicie sesión.' //$e->getMessage(),
            ],401);
        });

        $exceptions->render(function (AuthenticationException $e) {
            if (request()->is('api/*')) {
                return response()->json([
                    'success'=>false,
                    'message' => $e->getMessage(),
                ], 401);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'No encontrado.' //$e->getMessage(),
            ],404);
        });
        $exceptions->renderable(function (RouteNotFoundException $e){
            return response()->json([
                'success'=>false,
                'message'=> $e->getMessage(),
            ],404);
        });
        $exceptions->renderable(function (MethodNotAllowedHttpException $e){
            return response()->json([
                'success'=>false,
                'message'=> $e->getMessage(),
            ],405);
        });
        $exceptions->renderable(function (ConnectionException $e){
            return response()->json([
                'success'=>false,
                'message'=> "Hubo un error con la conexion error de servidor."
            ],500);
        });
        $exceptions->renderable(function (QueryException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'Error de consulta en base de datos.'
            ],500);
        });
        $exceptions->renderable(function (PDOException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'Error al insertar los datos en la base de datos.'
            ],500);
        });
        $exceptions->renderable(function (BadMethodCallException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'Error de servidor metodo invalido',
            ],500);
        });
        $exceptions->renderable(function (NotWritableException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'Sin permisos para escribir',
            ],403);
        });
        $exceptions->renderable(function (ErrorException $e){
            return response()->json([
                'success'=>false,
                'message'=> 'Error de servidor',
            ],500);
        });

})->create();
