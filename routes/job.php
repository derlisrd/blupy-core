<?php

use App\Http\Controllers\JobsControllers\JobsManualesController;
use Illuminate\Support\Facades\Route;


Route::get('/actualizar-tarjetas',[JobsManualesController::class,'actualizarTarjetas']);
