<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use App\Http\Controllers\BlupyApp\ValidacionesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;

Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);

Route::get('/verificar-documento',[ConsultasController::class,'verificarExisteDocumento']);
Route::get('/ciudades',[ConsultasController::class,'ciudades']);
Route::get('/barrios',[ConsultasController::class,'barrios']);
Route::post('/scan',[ValidacionesController::class,'scanearDocumento']);

Route::middleware(Authenticate::using('api'))->group(function(){

    Route::post('/check-token',[AuthController::class,'checkToken']);
    Route::post('/refresh-token',[AuthController::class,'refreshToken']);

});
