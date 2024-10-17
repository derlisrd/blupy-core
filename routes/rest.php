<?php

use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use App\Http\Controllers\Rest\ConsultasController;
use App\Http\Controllers\Rest\NotificacionesController;
use App\Http\Controllers\Rest\SolicitudesController;
use App\Http\Controllers\Rest\UsersController;
use App\Http\Controllers\Rest\VendedoresController;
use App\Http\Controllers\Rest\VentasFarmaController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;



Route::post('login',[AuthController::class,'login'])->name('rest_login');

Route::middleware(Authenticate::using('api'))->group(function(){


    Route::put('/actualizar-solicitudes',[SolicitudesController::class,'actualizarSolicitudes'])->name('rest_actualizar_solicitudes');
    Route::get('/actualizar-solicitud',[SolicitudesController::class,'actualizarSolicitud'])->name('rest_actualizar_solicitud');

    Route::get('/clientes',[ClientesController::class,'index'])->name('rest_clientes');
    Route::post('/clientes-filtros',[ClientesController::class,'filtros'])->name('rest_clientes_filtros');
    Route::get('/cliente',[ClientesController::class,'buscar'])->name('rest_cliente');
    Route::get('/cliente/restablecer-contrasena',[ClientesController::class,'restablecerContrasena'])->name('rest_cliente_restablecer_contrasena');
    Route::get('/cliente/ficha/{id}',[ClientesController::class,'ficha'])->name('rest_cliente_ficha');


    Route::get('/check',[AuthController::class,'checkToken'])->name('rest_check_token');

    Route::post('enviar-notificacion',[NotificacionesController::class,'enviarNotificacion'])->name('rest_enviar_notificacion');

    Route::post('/enviar-notificaciones-masivas',[NotificacionesController::class,'enviarNotificacionesMasivas'])->name('rest_enviar_notificaciones_masivas');

    Route::post('/enviar-notificacion-selectiva',[NotificacionesController::class,'enviarNotificacionSelectiva'])->name('rest_enviar_notificacion_selectiva');

    Route::get('/solicitudes',[SolicitudesController::class,'index'])->name('rest_solicitudes');
    Route::post('/solicitudes-filtros',[SolicitudesController::class,'filtros'])->name('rest_solicitudes_filtros');
    Route::get('/solicitud',[SolicitudesController::class,'buscar'])->name('rest_solicitud');
    Route::get('/totales',[SolicitudesController::class,'totales'])->name('rest_totales');

    Route::get('/consultas/farma',[ConsultasController::class,'farma'])->name('rest_consulta_farma');

    Route::post('/ingresar-vendedor',[VendedoresController::class,'ingresarVendedor'])->name('rest_ingresar_vendedor');

    Route::post('/restablecer-contrasena',[UsersController::class,'restablecerContrasena'])->name('rest_restablecer_contrasena');


    Route::post('/actualizar-perfiles',[UsersController::class,'actualizarPerfiles'])->name('rest_actualizar_perfiles');

    //Route::get('/ventas-dia-farma',[VentasFarmaController::class,'VentaDiaFarma']);

});


