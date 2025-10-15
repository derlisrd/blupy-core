<?php

use App\Http\Controllers\Web\PreRegistroController;
use Illuminate\Support\Facades\Route;



//Route::get('/pre-registro',[PreRegistroController::class, 'preRegistro'])->name('pre-registro');
//Route::post('/pre-registro',[PreRegistroController::class, 'store'])->name('pre-registro.store');

Route::view('/politicas-de-privacidad','politicas');
Route::view('/terminos','terminos');
Route::get('/', function () {
    return view('welcome');
});
