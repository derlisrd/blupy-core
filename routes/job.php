<?php

use App\Http\Controllers\JobsControllers\JobsManualesController;
use Illuminate\Support\Facades\Route;


Route::get('/actualizar-tarjetas',[JobsManualesController::class,'concluido']);
Route::get('/actualizar-perfiles-funcionarios',[JobsManualesController::class,'concluido']);