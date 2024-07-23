<?php

use App\Http\Controllers\Bancard\BancardController;
use Illuminate\Support\Facades\Route;


Route::get('/consultar-deuda',[BancardController::class,'consultarDeuda']);

Route::post('/pagar-deuda',[BancardController::class,'pagarDeuda']);

Route::put('/revertir-pago',[BancardController::class,'revertirPago']);
