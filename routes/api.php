<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;



Route::get('/verificar',[ConsultasController::class,'verificarDocumento']);
Route::get('/ciudades',[ConsultasController::class,'ciudades']);
Route::get('/barrios',[ConsultasController::class,'barrios']);
Route::post('/scan',[ConsultasController::class,'scanearDocumento']);

Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);



Route::middleware(Authenticate::using('api'))->group(function(){

    Route::post('check',[AuthController::class,'check']);

});
