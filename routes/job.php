<?php

use App\Http\Controllers\JobsControllers\JobsManualesController;
use Illuminate\Support\Facades\Route;


// Route::get('/actualizar-tarjetas',[JobsManualesController::class,'concluido']);
// Route::get('/actualizar-perfiles-funcionarios',[JobsManualesController::class,'concluido']);

//raatz
/* Route::get('/sumardeudas',[JobsManualesController::class,'sumarDeudas']);
Route::get('/extraercedula',[JobsManualesController::class,'extraerCedula']);
Route::get('/clientescondeudas',[JobsManualesController::class,'clientesConDeudas']); */

Route::post('/procesar-ventas-del-dia', [JobsManualesController::class, 'ingresarVentas']);
