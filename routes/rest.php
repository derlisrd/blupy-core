<?php

use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/export',[ClientesController::class,'export']);

Route::post('login',[AuthController::class,'login']);

Route::middleware(Authenticate::using('api'))->group(function(){

    Route::get('/clientes',[ClientesController::class,'index']);



});
