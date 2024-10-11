<?php

use App\Http\Controllers\Bancard\BancardController;
use Illuminate\Support\Facades\Route;


Route::get('/consultar-deuda',[BancardController::class,'consultarDeuda'])->name('bancard_consulta');

Route::post('/pagar-deuda',[BancardController::class,'pagarDeuda'])->name('bancard_pagar');

Route::put('/revertir-pago',[BancardController::class,'revertirPago'])->name('bancard_revertir');
