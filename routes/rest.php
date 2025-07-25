<?php

use App\Http\Controllers\Rest\AdminController;
use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use App\Http\Controllers\Rest\ConsultasController;
use App\Http\Controllers\Rest\ContratosController;
use App\Http\Controllers\Rest\EstadisticasController;
use App\Http\Controllers\Rest\JobsManualesController;
use App\Http\Controllers\Rest\NotificacionesController;
use App\Http\Controllers\Rest\PermisoAdminController;
use App\Http\Controllers\Rest\SolicitudesController;
use App\Http\Controllers\Rest\VentasController;
//use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;



Route::post('login',[AuthController::class,'login'])->name('rest_login');

Route::group(['middleware' => ['auth:admin']], function() {

    Route::get('/check',[AuthController::class,'checkToken'])->name('rest_check_token');

    Route::prefix('estadisticas')->group(function(){
        Route::get('/totales',[EstadisticasController::class,'totales']);
    });


    Route::prefix('clientes')->group(function(){
        Route::get('/',[ClientesController::class,'index'])->middleware('permiso.admin:clientes,ver')->name('rest_clientes');
        Route::post('/actualizar-foto-cedula/{id}',[ClientesController::class,'actualizarFotoCedula'])->name('rest_actualizar_foto_cedula');
        Route::post('/agregar-adjunto/{id}',[ClientesController::class,'agregarAdjunto'])->name('rest_agregar_abjuntos');
        Route::get('/adjuntos/{id}',[ClientesController::class,'adjuntos'])->name('rest_clientes_adjuntos');
        Route::post('/filtros',[ClientesController::class,'filtros'])->name('rest_clientes_filtros');
        Route::get('/buscar',[ClientesController::class,'buscar'])->name('rest_clientes_buscar');
        Route::get('/ficha/{id}',[ClientesController::class,'ficha'])->name('rest_cliente_ficha');
        Route::put('/restablecer-contrasena',[ClientesController::class,'restablecerContrasena'])->middleware('permiso.admin:clientes,restablecer_contrasena');
        Route::put('/estado',[ClientesController::class,'cambiarEstado'])->middleware('permiso.admin:clientes,cambiar_estado');
    });

    Route::prefix('micredito')->group(function(){
        Route::get('/movimientos',[ClientesController::class,'micredito'])->name('rest_micredito');
    });


    Route::put('/actualizar-ficha-funcionario',[ClientesController::class,'actualizarFuncionario'])->name('rest_actualizar_ficha_funcionario');


    Route::prefix('/notificacion')->group(function(){
        Route::post('/individual',[NotificacionesController::class,'individual'])->name('rest_enviar_notificacion_individual');
        Route::post('/wa',[NotificacionesController::class,'wa'])->name('rest_enviar_wa');
        Route::post('/difusion',[NotificacionesController::class,'difusion'])->middleware('permiso.admin:notificaciones,enviar_difusion_masiva');
        Route::post('/difusion-selectiva',[NotificacionesController::class,'difusionSelectiva'])->middleware('permiso.admin:notificaciones,enviar_difusion_selectiva');
        Route::get('/ficha',[NotificacionesController::class,'ficha'])->name('rest_notificacion_ficha');
        Route::post('/enviar-sms',[NotificacionesController::class,'enviarSms'])->name('rest_enviar_sms');
    });



    Route::prefix('jobs')
    ->middleware('permiso.admin:jobs,gestionar')
    ->group(function(){
        Route::post('/update-perfil-funcionarios',[JobsManualesController::class,'updatePerfilFuncionarios']);
        Route::post('/update-perfil-alianzas',[JobsManualesController::class,'updatePerfilAlianzas']);
        Route::post('/update-sucursales-farma',[JobsManualesController::class,'updateSucursalesFarma']);
        Route::post('/update-ventas-farma',[JobsManualesController::class,'updateVentasFarma']);
        Route::post('/update-cliente-digital',[JobsManualesController::class,'updateClienteDigital']);
    });




    Route::prefix('solicitudes')->group(function(){
        Route::get('/',[SolicitudesController::class,'index'])->name('rest_solicitudes');
        Route::get('/totales',[SolicitudesController::class,'totales'])->name('rest_solicitudes_totales');
        Route::get('/buscar',[SolicitudesController::class,'buscar'])->name('rest_solicitud_buscar');
        Route::get('/filtros',[SolicitudesController::class,'filtros'])->name('rest_solicitudes_filtros');
        Route::post('/aprobar',[SolicitudesController::class,'aprobar'])->middleware('permiso.admin:solicitud_creditos,aprobar');
        Route::put('/actualizar-solicitudes',[SolicitudesController::class,'actualizarSolicitudes'])->name('rest_actualizar_solicitudes');
        Route::put('/actualizar-solicitud',[SolicitudesController::class,'actualizarSolicitud'])->name('rest_actualizar_solicitud');
    });

    Route::prefix('contrato')->group(function(){
        Route::get('/cedula',[ContratosController::class,'contratoPorDocumento'])->name('rest_contratos');
        Route::get('/codigo',[ContratosController::class,'contratoPorCodigo'])->name('rest_contratos');
        Route::post('/recibir',[ContratosController::class,'recibirContrato'])->middleware('permiso.admin:contratos,recibir');;
    });

    Route::prefix('/consultas')->group(function(){
        Route::get('/cedula',[ConsultasController::class,'clienteFarmaMiCredito'])->name('rest_consulta_cliente');
        Route::get('/codigo',[ConsultasController::class,'clienteFarmaPorCodigo'])->name('rest_consulta_cliente');
        Route::get('/movimientos',[ConsultasController::class,'movimientos'])->name('rest_consulta_movimientos');
        Route::get('/info-sucursal',[ConsultasController::class,'infoSucursal']);
    });






    Route::prefix('ventas')->group(function(){
        Route::get('/',[VentasController::class,'index'])->name('rest_ventas');
        Route::get('/acumulados',[VentasController::class,'acumulados'])->name('rest_ventas_acumulados');
        Route::get('/acumulados-mes',[VentasController::class,'acumuladosMes'])->name('rest_ventas_acumulados_mes');
        Route::get('/periodo-forma',[VentasController::class,'periodoForma'])->name('rest_ventas_periodo_forma');
        Route::get('/por-codigo',[VentasController::class,'porCodigo'])->name('rest_venta_por_codigo');
        Route::get('/por-factura',[VentasController::class,'porFactura'])->name('rest_venta_por_factura');
    });


    Route::prefix('permisos')
    ->middleware('permiso.admin:permisos,asignar')
    ->group(function () {
        Route::get('/users-administradores', [PermisoAdminController::class, 'administradores']); // Todos los permisos disponibles
        Route::get('/', [PermisoAdminController::class, 'index']); // Todos los permisos disponibles
        Route::get('/by-admin/{id}', [PermisoAdminController::class, 'permisosByAdmin']); // Todos los permisos disponibles
        Route::post('/asignar', [PermisoAdminController::class, 'asignar']); // Asignar permisos
        Route::post('/revocar', [PermisoAdminController::class, 'revocar']); // Revocar permisos
    });

    Route::prefix('admin')->group(function () {
        Route::post('/reset-password', [AdminController::class, 'resetPassword'])
        ->middleware('permiso.admin:admins,reset_password');

        Route::post('/add', [AdminController::class, 'store'])
        ->middleware('permiso.admin:admins,crear');

    });

});



