<?php

use App\Http\Controllers\Rest\AuthController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;


Route::post('login',[AuthController::class,'login']);

Route::middleware(Authenticate::using('api'))->group(function(){
    Route::get('/solicitudes');
});
