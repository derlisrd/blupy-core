<?php

use App\Http\Controllers\Web\PreRegistroController;
use Illuminate\Support\Facades\Route;

Route::get('/terminos', function () {
    return view('terminos');
});
Route::get('/terminos2', function () {
    return view('terminos');
});
Route::get('/cumple', function () {
    return view('email.cumpleanios');
});


Route::get('/pre-registro',[PreRegistroController::class, 'preRegistro'])->name('pre-registro');
Route::post('/pre-registro',[PreRegistroController::class, 'store'])->name('pre-registro.store');
