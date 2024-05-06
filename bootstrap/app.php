<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Auth\AuthenticationException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        //apiPrefix:'/api',
        then:function(){
            Route::middleware('api')
            ->prefix('rest')
            ->name('rest')
            ->group(base_path('routes/rest.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->use([
            \App\Http\Middleware\XapiKeyTokenIsValid::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (AuthenticationException $e){
            return response()->json([
                'success'=>false,
                'message'=> $e->getMessage(),
            ],401);
        });

        $exceptions->render(function (AuthenticationException $e) {
            if (request()->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 401);
            }
        });

        $exceptions->renderable(function (NotFoundHttpException $e){
            return response()->json([
                'success'=>false,
                'message'=> $e->getMessage(),
            ],404);
        });
        $exceptions->renderable(function (RouteNotFoundException $e){
            return response()->json([
                'success'=>false,
                'message'=> $e->getMessage(),
            ],404);
        });


    })->create();
