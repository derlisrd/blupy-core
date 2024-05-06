<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;


Route::get('/home',function(){
    return response()->json([
        'success'=>true
    ]);
});

Route::middleware(Authenticate::using('sanctum'))->group(function(){

    Route::get('/me',function(){
        return response()->json([
            'success'=>true
        ]);
    });

});
