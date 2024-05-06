<?php

use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;



Route::get('/verificar',[ConsultasController::class,'verificarDocumento']);
Route::get('/ciudades',[ConsultasController::class,'verificarDocumento']);
Route::get('/barrios',[ConsultasController::class,'verificarDocumento']);

Route::post('/login',[AuthController::class,'login']);




Route::middleware(Authenticate::using('api'))->group(function(){

});
