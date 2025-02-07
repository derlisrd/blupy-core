<?php

use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use App\Http\Controllers\Rest\ConsultasController;
use App\Http\Controllers\Rest\InformesVentasController;
use App\Http\Controllers\Rest\NotificacionesController;
use App\Http\Controllers\Rest\SolicitudesController;
use App\Http\Controllers\Rest\UsersController;
use App\Http\Controllers\Rest\VendedoresController;
use App\Http\Controllers\Rest\VentasFarmaController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;



Route::post('login',[AuthController::class,'login'])->name('rest_login');

Route::middleware(Authenticate::using('api'))->group(function(){

    Route::get('/check',[AuthController::class,'checkToken'])->name('rest_check_token');

    Route::put('/actualizar-solicitudes',[SolicitudesController::class,'actualizarSolicitudes'])->name('rest_actualizar_solicitudes');
    Route::get('/actualizar-solicitud',[SolicitudesController::class,'actualizarSolicitud'])->name('rest_actualizar_solicitud');

    Route::get('/clientes',[ClientesController::class,'index'])->name('rest_clientes');
    Route::post('/clientes-filtros',[ClientesController::class,'filtros'])->name('rest_clientes_filtros');
    Route::get('/cliente',[ClientesController::class,'buscar'])->name('rest_cliente');
    //Route::get('/cliente/restablecer-contrasena',[ClientesController::class,'restablecerContrasena'])->name('rest_cliente_restablecer_contrasena');
    Route::get('/cliente/ficha/{id}',[ClientesController::class,'ficha'])->name('rest_cliente_ficha');

    Route::put('/actualizar-ficha-funcionario',[ClientesController::class,'actualizarFuncionario'])->name('rest_actualizar_ficha_funcionario');



    Route::post('enviar-notificacion',[NotificacionesController::class,'enviarNotificacion'])->name('rest_enviar_notificacion');
    Route::post('/enviar-notificaciones-masivas',[NotificacionesController::class,'enviarNotificacionesMasivas'])->name('rest_enviar_notificaciones_masivas');
    Route::post('/enviar-notificacion-selectiva',[NotificacionesController::class,'enviarNotificacionSelectiva'])->name('rest_enviar_notificacion_selectiva');


    Route::get('/solicitudes-filtros',[SolicitudesController::class,'filtros'])->name('rest_solicitudes_filtros');
    Route::get('/solicitud',[SolicitudesController::class,'buscar'])->name('rest_solicitud');
    Route::get('/totales',[SolicitudesController::class,'totales'])->name('rest_totales');


    Route::prefix('solicitudes')->group(function(){
        Route::get('/',[SolicitudesController::class,'index'])->name('rest_solicitudes');
        Route::post('/aprobar',[SolicitudesController::class,'aprobar'])->name('rest_aprobar_solicitud');
    });


    Route::get('/consultas/cliente',[ConsultasController::class,'clienteFarmaMiCredito'])->name('rest_consulta_cliente');

    Route::post('/ingresar-vendedor',[VendedoresController::class,'ingresarVendedor'])->name('rest_ingresar_vendedor');

    Route::post('/restablecer-contrasena',[UsersController::class,'restablecerContrasena'])->name('rest_restablecer_contrasena');

    Route::post('/actualizar-perfiles',[UsersController::class,'actualizarPerfiles'])->name('rest_actualizar_perfiles');





    Route::get('/ventas-tickets',[VentasFarmaController::class,'tickets'])->name('rest_ventas_tickets');


    Route::prefix('ventas')->group(function(){

        Route::get('/totales',[VentasFarmaController::class,'ventasTotales'])->name('rest_ventas_totales');
        Route::get('/actualizar-del-dia',[VentasFarmaController::class,'ventasDiaFarmaJob'])->name('rest_actualizar_ventas_dia');
        Route::get('/dia-farma',[VentasFarmaController::class,'ventasDiaFarma'])->name('rest_ventas_dia_farma');
        Route::get('/hoy',[VentasFarmaController::class,'actualizarListaVentasDeHoy'])->name('rest_ventas_hoy_farma');
        Route::get('/porcentaje-uso',[VentasFarmaController::class,'porcentajeDeUsoBlupy'])->name('rest_porcentaje_uso');

        Route::get('/del-mes',[VentasFarmaController::class,'ventasDelMes']);

        Route::get('/por-sucursal',[VentasFarmaController::class,'ventasPorSucursal'])->name('rest_ventas_por_sucursal');

        Route::get('/comparar-meses',[InformesVentasController::class,'compararMeses'])->name('rest_comparar_meses');
        Route::get('/top-sucursales-tickets',[InformesVentasController::class,'topSucursalesTickets'])->name('rest_top_sucursales_tickets');
        Route::get('/top-sucursales-ingresos',[InformesVentasController::class,'topSucursalesIngresos'])->name('rest_top_sucursales_ingresos');
        Route::get('/dia-mas-venta',[InformesVentasController::class,'diaMasVenta'])->name('rest_dia_mas_ventas');
        Route::get('/mes-mas-venta',[InformesVentasController::class,'mesMayorFacturacion'])->name('rest_mes_mas_ventas');
        Route::get('/forma-pago',[InformesVentasController::class,'formaPago'])->name('rest_forma_pago');
    });



});




