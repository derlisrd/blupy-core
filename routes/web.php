<?php

use App\Http\Controllers\Web\PreRegistroController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;



//Route::get('/pre-registro',[PreRegistroController::class, 'preRegistro'])->name('pre-registro');
//Route::post('/pre-registro',[PreRegistroController::class, 'store'])->name('pre-registro.store');

Route::view('/politicas-de-privacidad', 'politicas');

Route::view('/terminos', 'terminos');
Route::view('/datos-crediticios', 'datoscrediticios');

Route::get('/', function () {
    return view('welcome');
});


/* Route::get('/send-email', function () {
    Mail::raw('Mensaje de prueba', function ($message) {
        $message->to('derlisruizdiazr@gmail.com')
            ->subject('Asunto de prueba');
    });
    return 'Correo electrónico enviado correctamente';
});
 */