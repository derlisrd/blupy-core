<?php

use App\Http\Controllers\Rest\AdminController;
use App\Http\Controllers\Rest\AuthController;
use App\Http\Controllers\Rest\ClientesController;
use App\Http\Controllers\Rest\ConsultasController;
use App\Http\Controllers\Rest\ContratosController;
use App\Http\Controllers\Rest\DevicesController;
use App\Http\Controllers\Rest\EstadisticasController;
use App\Http\Controllers\Rest\JobsManualesController;
use App\Http\Controllers\Rest\MorososController;
use App\Http\Controllers\Rest\NotificacionesController;
use App\Http\Controllers\Rest\PermisoAdminController;
use App\Http\Controllers\Rest\SolicitudesController;
use App\Http\Controllers\Rest\VentasController;
//use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;


// RUTAS PÚBLICAS
Route::post('login',[AuthController::class,'login'])->name('rest_login');

// RUTAS PROTEGIDAS POR AUTH:ADMIN
Route::group(['middleware' => ['auth:admin']], function() {

    Route::get('/check',[AuthController::class,'checkToken'])->name('rest_check_token');

    // ESTADÍSTICAS
    Route::prefix('estadisticas')->group(function(){
        Route::get('/totales',[EstadisticasController::class,'totales'])->name('rest_estadisticas_totales'); // << AÑADIDO
    });


    // CLIENTES
    Route::prefix('clientes')->group(function(){
        Route::get('/',[ClientesController::class,'index'])->middleware('permiso.admin:clientes,ver')->name('rest_clientes_index'); // << CORREGIDO (index)
        Route::post('/actualizar-foto-cedula/{id}',[ClientesController::class,'actualizarFotoCedula'])->name('rest_actualizar_foto_cedula');
        Route::post('/agregar-adjunto/{id}',[ClientesController::class,'agregarAdjunto'])->name('rest_agregar_abjuntos');
        Route::get('/adjuntos/{id}',[ClientesController::class,'adjuntos'])->name('rest_clientes_adjuntos');
        Route::post('/filtros',[ClientesController::class,'filtros'])->name('rest_clientes_filtros');
        Route::get('/buscar',[ClientesController::class,'buscar'])->name('rest_clientes_buscar');
        Route::get('/ficha/{id}',[ClientesController::class,'ficha'])->name('rest_cliente_ficha');
        Route::put('/restablecer-contrasena',[ClientesController::class,'restablecerContrasena'])->middleware('permiso.admin:clientes,restablecer_contrasena')->name('rest_clientes_restablecer_contrasena');
        Route::put('/estado',[ClientesController::class,'cambiarEstado'])->middleware('permiso.admin:clientes,cambiar_estado')->name('rest_clientes_cambiar_estado');
    });

    // MICREDITO
    Route::prefix('micredito')->group(function(){
        Route::get('/movimientos',[ClientesController::class,'micredito'])->name('rest_micredito_movimientos'); // << CORREGIDO (más específico)
    });


    // FUNCIONARIO
    Route::put('/actualizar-ficha-funcionario',[ClientesController::class,'actualizarFuncionario'])->name('rest_actualizar_ficha_funcionario');


    // NOTIFICACIONES
    Route::prefix('/notificacion')->group(function(){
        Route::post('/individual',[NotificacionesController::class,'individual'])->name('rest_enviar_notificacion_individual');
        Route::post('/wa',[NotificacionesController::class,'wa'])->name('rest_enviar_wa');
        Route::post('/difusion',[NotificacionesController::class,'difusion'])->middleware('permiso.admin:notificaciones,enviar_difusion_masiva')->name('rest_difusion');
        
        Route::post('/difusion-selectiva',[NotificacionesController::class,'difusionSelectiva'])->middleware('permiso.admin:notificaciones,enviar_difusion_selectiva')->name('rest_selectiva');
        Route::post('/sms-to-morosos',[NotificacionesController::class,'smsToMorosos'])->name('rest_sms_to_morosos');
        
        Route::get('/ficha',[NotificacionesController::class,'ficha'])->name('rest_notificacion_ficha');
        
        Route::post('/enviar-sms',[NotificacionesController::class,'enviarSms'])->name('rest_enviar_sms');
    });

    // NOTIFICACIONES
    Route::prefix('/notificacion')->group(function(){
        Route::post('/individual',[NotificacionesController::class,'individual'])->name('rest_enviar_notificacion_individual');
        Route::post('/wa',[NotificacionesController::class,'wa'])->name('rest_enviar_wa');
        Route::post('/difusion',[NotificacionesController::class,'difusion'])->middleware('permiso.admin:notificaciones,enviar_difusion_masiva')->name('rest_difusion');
        
        Route::post('/difusion-selectiva',[NotificacionesController::class,'difusionSelectiva'])->middleware('permiso.admin:notificaciones,enviar_difusion_selectiva')->name('rest_selectiva');
        Route::post('/sms-to-morosos',[NotificacionesController::class,'smsToMorosos'])->name('rest_sms_to_morosos');
        
        Route::get('/ficha',[NotificacionesController::class,'ficha'])->name('rest_notificacion_ficha');
        
        Route::post('/enviar-sms',[NotificacionesController::class,'enviarSms'])->name('rest_enviar_sms');
    });

    // NOTIFICACIONES
    Route::prefix('/morosos')->group(function(){
        Route::post('/reclamo-sms-excel',[MorososController::class,'reclamoPorSmsConListadoCSV']);
    });


    // JOBS MANUALES
    Route::prefix('jobs')
    ->middleware('permiso.admin:jobs,gestionar')
    ->group(function(){
        Route::post('/update-perfil-funcionarios',[JobsManualesController::class,'updatePerfilFuncionarios'])->name('rest_jobs_update_perfil'); // << CORREGIDO
        Route::post('/update-perfil-alianzas',[JobsManualesController::class,'updatePerfilAlianzas'])->name('rest_jobs_update_alianzas'); // << CORREGIDO
        Route::post('/update-sucursales-farma',[JobsManualesController::class,'updateSucursalesFarma'])->name('rest_jobs_update_sucursales'); // << CORREGIDO
        Route::post('/update-ventas-farma',[JobsManualesController::class,'updateVentasFarma'])->name('rest_jobs_ventas'); // << CORREGIDO
        Route::post('/update-cliente-digital',[JobsManualesController::class,'updateClienteDigital'])->name('rest_jobs_digital'); // << CORREGIDO
        Route::post('/update-solicitudes-pendientes',[JobsManualesController::class,'updateSolicitudesPendientes'])->name('rest_jobs_solicitudes_pendientes'); // << CORREGIDO
    });


    // SOLICITUDES
    Route::prefix('solicitudes')->group(function(){
        Route::get('/',[SolicitudesController::class,'index'])->name('rest_solicitudes_index'); // << CORREGIDO (index)
        Route::get('/totales',[SolicitudesController::class,'totales'])->name('rest_solicitudes_totales');
        Route::get('/buscar',[SolicitudesController::class,'buscar'])->name('rest_solicitud_buscar');
        Route::get('/filtros',[SolicitudesController::class,'filtros'])->name('rest_solicitudes_filtros');
        Route::post('/aprobar',[SolicitudesController::class,'aprobar'])->middleware('permiso.admin:solicitud_creditos,aprobar')->name('rest_solicitudes_aprobar'); // << AÑADIDO
        Route::put('/actualizar-solicitudes',[SolicitudesController::class,'actualizarSolicitudes'])->name('rest_actualizar_solicitudes');
        Route::put('/actualizar-solicitud',[SolicitudesController::class,'actualizarSolicitud'])->name('rest_actualizar_solicitud');
    });

    // CONTRATOS
    Route::prefix('contrato')->group(function(){
        // Corregido: Duplicación de nombre en /cedula y /codigo. Se renombra /cedula.
        Route::get('/impresos-en-farma',[ContratosController::class,'contratosImpresosEnFarma'])->name('rest_contratos_impresos');
        Route::get('/cedula',[ContratosController::class,'contratoPorDocumento'])->name('rest_contratos_por_cedula');
        Route::get('/codigo',[ContratosController::class,'contratoPorCodigo'])->name('rest_contratos_por_codigo');
        Route::post('/recibir',[ContratosController::class,'recibirContrato'])->middleware('permiso.admin:contratos,recibir')->name('rest_contratos_recibir'); // << AÑADIDO
    });

    // CONSULTAS
    Route::prefix('/consultas')->group(function(){
        // Corregido: Duplicación de nombre en /cedula y /codigo. Se renombra /cedula.
        Route::get('/cedula',[ConsultasController::class,'clienteFarmaMiCredito'])->name('rest_consulta_cliente_cedula');
        Route::get('/codigo',[ConsultasController::class,'clienteFarmaPorCodigo'])->name('rest_consulta_cliente_codigo');
        Route::get('/movimientos',[ConsultasController::class,'movimientos'])->name('rest_consulta_movimientos');
        Route::get('/info-sucursal',[ConsultasController::class,'infoSucursal'])->name('rest_consulta_info_sucursal'); // << AÑADIDO
    });


    // VENTAS
    Route::prefix('ventas')->group(function(){
        Route::get('/',[VentasController::class,'index'])->name('rest_ventas_index'); // << CORREGIDO (index)
        Route::get('/acumulados',[VentasController::class,'acumulados'])->name('rest_ventas_acumulados');
        Route::get('/acumulados-mes',[VentasController::class,'acumuladosMes'])->name('rest_ventas_acumulados_mes');
        Route::get('/periodo-forma',[VentasController::class,'periodoForma'])->name('rest_ventas_periodo_forma');
        Route::get('/por-codigo',[VentasController::class,'porCodigo'])->name('rest_venta_por_codigo');
        Route::get('/por-factura',[VentasController::class,'porFactura'])->name('rest_venta_por_factura');
    });


    // PERMISOS
    Route::prefix('permisos')
    ->middleware('permiso.admin:permisos,asignar')
    ->group(function () {
        Route::get('/users-administradores', [PermisoAdminController::class, 'administradores'])->name('rest_permisos_administradores'); // << AÑADIDO
        Route::get('/', [PermisoAdminController::class, 'index'])->name('rest_permisos_index'); // << AÑADIDO
        Route::get('/by-admin/{id}', [PermisoAdminController::class, 'permisosByAdmin'])->name('rest_permisos_by_admin'); // << AÑADIDO
        Route::post('/asignar', [PermisoAdminController::class, 'asignar'])->name('rest_permisos_asignar'); // << AÑADIDO
        Route::post('/revocar', [PermisoAdminController::class, 'revocar'])->name('rest_permisos_revocar'); // << AÑADIDO
    });

    // ADMINS (USUARIOS ADMINISTRADORES)
    Route::prefix('admin')->group(function () {
        Route::post('/reset-password', [AdminController::class, 'resetPassword'])
        ->middleware('permiso.admin:admins,reset_password')
        ->name('rest_admin_reset_password');

        Route::post('/add', [AdminController::class, 'store'])
        ->middleware('permiso.admin:admins,crear')
        ->name('rest_admin_add'); 
    });


    Route::prefix('/devices')->group(function () {
        Route::get('/solicitudes', [DevicesController::class, 'listado'])->name('rest_devices_listado');
        Route::get('/aprobadr', [DevicesController::class, 'aprobar'])->name('rest_devices_aprobar');
    });

});