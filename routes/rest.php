<?php

use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use App\Http\Controllers\Rest\ConsultasController;
use App\Http\Controllers\Rest\NotificacionesController;
use App\Http\Controllers\Rest\SolicitudesController;
use App\Http\Controllers\Rest\UsersController;
use App\Http\Controllers\Rest\VendedoresController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

Route::get('/export',[ClientesController::class,'export']);

Route::post('login',[AuthController::class,'login']);

Route::middleware(Authenticate::using('api'))->group(function(){


    Route::put('/actualizar-solicitudes',[SolicitudesController::class,'actualizarSolicitudes']);
    Route::get('/actualizar-solicitud',[SolicitudesController::class,'actualizarSolicitud']);

    Route::get('/clientes',[ClientesController::class,'index']);
    Route::post('/clientes-filtros',[ClientesController::class,'filtros']);
    Route::get('/cliente',[ClientesController::class,'buscar']);
    Route::get('/cliente/restablecer-contrasena',[ClientesController::class,'restablecerContrasena']);
    Route::get('/cliente/ficha/{id}',[ClientesController::class,'ficha']);


    Route::get('/check',[AuthController::class,'checkToken']);

    Route::post('enviar-notificacion',[NotificacionesController::class,'enviarNotificacion']);

    Route::post('/enviar-notificaciones-masivas',[NotificacionesController::class,'enviarNotificacionesMasivas']);

    Route::post('/enviar-notificacion-selectiva',[NotificacionesController::class,'enviarNotificacionSelectiva']);

    Route::get('/solicitudes',[SolicitudesController::class,'index']);
    Route::post('/solicitudes-filtros',[SolicitudesController::class,'filtros']);
    Route::get('/solicitud',[SolicitudesController::class,'buscar']);
    Route::get('/totales',[SolicitudesController::class,'totales']);

    Route::get('/consultas/farma',[ConsultasController::class,'farma']);

    Route::post('/ingresar-vendedor',[VendedoresController::class,'ingresarVendedor']);

    Route::post('/restablecer-contrasena',[UsersController::class,'restablecerContrasena']);


    Route::post('/actualizar-perfiles',[UsersController::class,'actualizarPerfiles']);

});


