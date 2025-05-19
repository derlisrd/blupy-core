<?php

use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use App\Http\Controllers\Rest\ConsultasController;
use App\Http\Controllers\Rest\ContratosController;
use App\Http\Controllers\Rest\EstadisticasController;
use App\Http\Controllers\Rest\JobsManualesController;
use App\Http\Controllers\Rest\NotificacionesController;
use App\Http\Controllers\Rest\SolicitudesController;
use App\Http\Controllers\Rest\UsersController;
use App\Http\Controllers\Rest\VentasController;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;



Route::post('login',[AuthController::class,'login'])->name('rest_login');

Route::middleware(Authenticate::using('api'))->group(function(){

    Route::get('/check',[AuthController::class,'checkToken'])->name('rest_check_token');

    Route::prefix('estadisticas')->group(function(){
        Route::get('/totales',[EstadisticasController::class,'totales']);
    });


    Route::prefix('clientes')->group(function(){
        Route::get('/',[ClientesController::class,'index'])->name('rest_clientes');
        Route::post('/actualizar-foto-cedula/{id}',[ClientesController::class,'actualizarFotoCedula'])->name('rest_actualizar_foto_cedula');
        Route::put('/agregar-adjunto/{id}',[ClientesController::class,'agregarAdjunto'])->name('rest_agregar_abjuntos');
        Route::get('/adjuntos/{id}',[ClientesController::class,'adjuntos'])->name('rest_clientes_adjuntos');
        Route::post('/filtros',[ClientesController::class,'filtros'])->name('rest_clientes_filtros');
        Route::get('/buscar',[ClientesController::class,'buscar'])->name('rest_clientes_buscar');
        Route::get('/ficha/{id}',[ClientesController::class,'ficha'])->name('rest_cliente_ficha');
        Route::put('/restablecer-contrasena',[ClientesController::class,'restablecerContrasena'])->name('rest_cliente_restablecer_contrasena');
        Route::put('/estado',[ClientesController::class,'cambiarEstado'])->name('rest_cliente_cambiar_estado');
    });

    Route::prefix('micredito')->group(function(){
        Route::get('/movimientos',[ClientesController::class,'micredito'])->name('rest_micredito');
    });


    Route::put('/actualizar-ficha-funcionario',[ClientesController::class,'actualizarFuncionario'])->name('rest_actualizar_ficha_funcionario');


    Route::prefix('/notificacion')->group(function(){
        Route::post('/individual',[NotificacionesController::class,'individual'])->name('rest_enviar_notificacion_individual');
        Route::post('/wa',[NotificacionesController::class,'wa'])->name('rest_enviar_wa');
        Route::post('/difusion',[NotificacionesController::class,'difusion'])->name('rest_enviar_notificaciones_masivas');
        Route::post('/difusion-selectiva',[NotificacionesController::class,'difusionSelectiva'])->name('rest_enviar_notificaciones_masivas_selectiva');
        Route::get('/ficha',[NotificacionesController::class,'ficha'])->name('rest_notificacion_ficha');
        Route::post('/enviar-sms',[NotificacionesController::class,'enviarSms'])->name('rest_enviar_sms');
    });



    Route::prefix('jobs')->group(function(){
        Route::post('/update-perfil-funcionarios',[JobsManualesController::class,'updatePerfilFuncionarios'])->name('rest_update_perfil_funcionarios');
        Route::post('/update-perfil-alianzas',[JobsManualesController::class,'updatePerfilAlianzas'])->name('rest_update_perfil_alianzas');
        Route::post('/update-sucursales-farma',[JobsManualesController::class,'updateSucursalesFarma'])->name('rest_update_sucursales_farma');
        Route::post('/update-ventas-farma',[JobsManualesController::class,'updateVentasFarma'])->name('rest_update_ventas_farma');
        Route::post('/update-cliente-digital',[JobsManualesController::class,'updateClienteDigital'])->name('rest_update_cliente_digital');
    });




    Route::prefix('solicitudes')->group(function(){
        Route::get('/',[SolicitudesController::class,'index'])->name('rest_solicitudes');
        Route::get('/totales',[SolicitudesController::class,'totales'])->name('rest_solicitudes_totales');
        Route::get('/buscar',[SolicitudesController::class,'buscar'])->name('rest_solicitud_buscar');
        Route::get('/filtros',[SolicitudesController::class,'filtros'])->name('rest_solicitudes_filtros');
        Route::post('/aprobar',[SolicitudesController::class,'aprobar'])->name('rest_aprobar_solicitud');
        Route::put('/actualizar-solicitudes',[SolicitudesController::class,'actualizarSolicitudes'])->name('rest_actualizar_solicitudes');
        Route::put('/actualizar-solicitud',[SolicitudesController::class,'actualizarSolicitud'])->name('rest_actualizar_solicitud');
    });

    Route::prefix('contrato')->group(function(){
        Route::get('/cedula',[ContratosController::class,'contratoPorDocumento'])->name('rest_contratos');
        Route::get('/codigo',[ContratosController::class,'contratoPorCodigo'])->name('rest_contratos');
    });

    Route::prefix('/consultas')->group(function(){
        Route::get('/cedula',[ConsultasController::class,'clienteFarmaMiCredito'])->name('rest_consulta_cliente');
        Route::get('/codigo',[ConsultasController::class,'clienteFarmaPorCodigo'])->name('rest_consulta_cliente');
        Route::get('/movimientos',[ConsultasController::class,'movimientos'])->name('rest_consulta_movimientos');
    });






    Route::prefix('ventas')->group(function(){
        Route::get('/',[VentasController::class,'index'])->name('rest_ventas');
        Route::get('/acumulados',[VentasController::class,'acumulados'])->name('rest_ventas_acumulados');
        Route::get('/acumulados-mes',[VentasController::class,'acumuladosMes'])->name('rest_ventas_acumulados_mes');
        Route::get('/periodo-forma',[VentasController::class,'periodoForma'])->name('rest_ventas_periodo_forma');
        Route::get('/por-codigo',[VentasController::class,'porCodigo'])->name('rest_venta_por_codigo');
        Route::get('/por-factura',[VentasController::class,'porFactura'])->name('rest_venta_por_factura');
    });




});



