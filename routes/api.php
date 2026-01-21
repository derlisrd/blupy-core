<?php

use App\Http\Controllers\BlupyApp\Auth\LoginController;
use App\Http\Controllers\BlupyApp\Auth\RegisterController;
//use App\Http\Controllers\BlupyApp\AuthController;
use App\Http\Controllers\BlupyApp\AutorizacionesQRController;
use App\Http\Controllers\BlupyApp\AWSController;
use App\Http\Controllers\BlupyApp\ClienteController;
use App\Http\Controllers\BlupyApp\ConsultasController;
use App\Http\Controllers\BlupyApp\ContratoController;
use App\Http\Controllers\BlupyApp\CuentasController;
use App\Http\Controllers\BlupyApp\DatosController;
use App\Http\Controllers\BlupyApp\DeviceController;
use App\Http\Controllers\BlupyApp\FarmaController;
use App\Http\Controllers\BlupyApp\InformacionesController;
use App\Http\Controllers\BlupyApp\MovimientosController;
use App\Http\Controllers\BlupyApp\NotificacionesController;
use App\Http\Controllers\BlupyApp\PdfController;
use App\Http\Controllers\BlupyApp\QRController;
use App\Http\Controllers\BlupyApp\SolicitudesController;
use App\Http\Controllers\BlupyApp\UserController as UserPrivate;
use App\Http\Controllers\Public\UserController as UserPublic;
use App\Http\Controllers\BlupyApp\ValidacionesController;
use App\Http\Controllers\BlupyApp\VendedorController;
use App\Http\Controllers\Public\VersionController;

use Illuminate\Support\Facades\Route;


Route::get('/vendedor/{id}',[VendedorController::class,'consultar']);



Route::post('/confirmar-nuevo-dispositivo',[DeviceController::class,'confirmarNuevoDispositivo'])->name('api_confirmar_nuevo_dispositivo');
Route::post('/codigo-nuevo-dispositivo',[DeviceController::class,'codigoNuevoDispositivo'])->name('api_codigo_nuevo_dispositivo');


Route::post('/olvide-contrasena',[UserPublic::class,'olvideContrasena'])->name('api_olvide_contrasena');
Route::post('/reenviar-codigo-recuperacion-wa',[UserPublic::class,'reenviarCodigoRecuperacionWa'])->name('api_reenviar_codigo_recuperacion_wa');
Route::post('/validar-codigo-recuperacion',[UserPublic::class,'validarCodigoRecuperacion'])->name('api_validar_codigo_recuperacion');
Route::post('/restablecer-contrasena',[UserPublic::class,'restablecerContrasena'])->name('api_restablecer_contrasena');

Route::get('/verificar-documento',[ConsultasController::class,'verificarExisteDocumento'])->name('api_verificar_documento');


Route::prefix('/auth')->group(function(){
    Route::post('/login',[LoginController::class,'login']);
    Route::post('/register',[RegisterController::class,'register']);
});

Route::prefix('/scan')->group(function(){
    Route::post('/cedula',[AWSController::class,'escanearCedula']);
    Route::post('/selfie-cedula',[AWSController::class,'escanearSelfieConCedula']);
});

Route::prefix('/validar')->group(function(){
    Route::get('/enviar-codigo-sms',[ValidacionesController::class,'enviarCodigoPorSmsParaValidarNroTelefono']);
    Route::get('/reenviar-codigo-wa',[ValidacionesController::class,'reEnviarCodigoPorWaParaValidarNroTelefono']);
    Route::get('/reenviar-codigo-sms',[ValidacionesController::class,'reEnviarCodigoPorSmsParaValidarNroTelefono']);
    Route::post('/confirmar-codigo-sms',[ValidacionesController::class,'confirmarCodigoParaValidarNroTelefono']);
    
    Route::get('/enviar-codigo-email',[ValidacionesController::class,'enviarCodigoPorEmail']);
    Route::post('/confirmar-codigo-email',[ValidacionesController::class,'confirmarCodigoParaValidarEmail']);
});
/**
 * RUTAS NUEVAS
 */


Route::post('/scan',[AWSController::class,'scanearDocumento'])->name('api_scan');
Route::post('/scan-selfie',[AWSController::class,'scanSelfieCedula'])->name('api_scan_selfie_id_card');

Route::post('/validar-email',[ValidacionesController::class,'validarEmail'])->name('api_validar_email');
Route::post('/confirmar-email',[ValidacionesController::class,'confirmarEmail'])->name('api_confirmar_email');
Route::post('/validar-telefono',[ValidacionesController::class,'validarTelefono'])->name('api_validar_telefono');
Route::post('/confirmar-telefono',[ValidacionesController::class,'confirmarTelefono'])->name('api_confirmar_telefono');

Route::get('/enviame-codigo-sms',[ValidacionesController::class,'enviameCodigoSMS'])->name('api_enviame_codigo_sms');

Route::post('/re-enviar-codigo-wa', [ValidacionesController::class, 'reEnviarCodigoPorWa']);

Route::get('/verificar-version',[VersionController::class,'verificarVersion'])->name('api_verificar_version');



Route::prefix('/dispositivos')->group(function () {
    Route::post('/solicitar', [DeviceController::class, 'requestNewDevice'])->name('api_solicitar_device');
});

/*
 =========RUTAS PROTEGIDAS ==================
 */
Route::middleware('auth:api')->group(function(){

    


    Route::get('/info',[InformacionesController::class,'infoPopUpInicial'])->name('api_info');
    Route::get('/info-lista',[InformacionesController::class,'infoLista'])->name('api_info_lista');
    Route::put('/marcar-info-leida/{id}',[InformacionesController::class,'marcarInfoLeida'])->name('api_marcar_info');

    Route::get('/mis-dispositivos',[CuentasController::class,'misDispositivos'])->name('api_mis_dispositivos');
    Route::delete('/eliminar-dispositivo',[CuentasController::class,'eliminarDispositivo'])->name('api_eliminar_dispositivo');

    Route::get('/notificaciones',[NotificacionesController::class,'NotificacionesPorUser'])->name('api_notificaciones');


    
    Route::prefix('/contratos')->group(function(){
        Route::post('/aceptar-blupy-digital',[ContratoController::class,'aceptarContrato'])->name('aceptar_contrato_blupy_digital');
    });

    Route::prefix('/cuenta')->group(function(){
        Route::get('/tarjetas',[CuentasController::class,'tarjetas2'])->name('api_tarjetas_2');
    });
    Route::get('/tarjetas',[CuentasController::class,'tarjetas'])->name('api_tarjetas');
    Route::get('/movimientos',[MovimientosController::class,'movimientos'])->name('api_movimientos');
    Route::get('/extracto',[CuentasController::class,'extracto'])->name('api_extracto');

    Route::get('/solicitudes',[SolicitudesController::class,'solicitudes'])->name('api_solicitudes');
    Route::post('/solicitar-credito',[SolicitudesController::class,'solicitarCredito'])->name('api_solicitar_credito');
    Route::post('/solicitar-ampliacion',[SolicitudesController::class,'solicitarAmpliacion'])->name('api_solicitar_ampliacion');
    Route::post('/agregar-adicional',[SolicitudesController::class,'agregarAdicional'])->name('api_agregar_adicional');
    Route::get('/verificar-estado-solicitud',[SolicitudesController::class,'verificarEstadoSolicitud'])->name('api_verificar_estado_solicitud');

    Route::put('/cambiar-contrasena',[UserPrivate::class,'cambiarContrasena'])->name('api_cambiar_contrasena');

    Route::put('/solicitar-cambiar-celular',[DatosController::class,'solicitarCambiarCelular'])->name('api_solicitar_cambiar_celular');
    Route::put('/confirmar-cambiar-celular',[DatosController::class,'confirmarCambiarCelular'])->name('api_confirmar_cambiar_celular');
    Route::put('/solicitar-cambiar-email',[DatosController::class,'solicitarCambiarEmail'])->name('api_solicitar_cambiar_email');
    Route::put('/confirmar-cambiar-email',[DatosController::class,'confirmarCambiarEmail'])->name('api_confirmar_cambiar_email');

    Route::get('/consultar-qr/{id}',[QRController::class,'consultar'])->name('api_consultar_qr');
    Route::post('/autorizar-qr',[QRController::class,'autorizar'])->name('api_autorizar_qr');

    Route::get('/solicitar-autorizacion-compra',[AutorizacionesQRController::class,'solicitarAutorizacion'])->name('api_solicitar_compra');
    Route::post('/autorizar-compra',[AutorizacionesQRController::class,'autorizar'])->name('api_autorizar_compra');

    Route::prefix('/solicitudes')->group(function(){
        Route::post('/solicitar-credito-digital',[SolicitudesController::class,'solicitarCreditoDigital']);
        Route::get('/cancelar',[SolicitudesController::class,'cancelarSolicitud']);
        Route::get('/verificar-disponibilidad',[SolicitudesController::class,'verificarDisponibilidad']);
    });

    Route::prefix('/consultas')->group(function(){
        Route::get('/ciudades',[ConsultasController::class,'ciudades'])->name('api_ciudades2');
        Route::get('/barrios',[ConsultasController::class,'barrios'])->name('api_barrios');
        Route::get('/barrios-por-ciudad/{idCiudad}',[ConsultasController::class,'barriosPorCiudad'])->name('api_barrios_por_ciudad');
        Route::get('/tipos-laboral',[ConsultasController::class,'tiposLaboral'])->name('api_tipos_laboral');
        Route::get('/profesiones',[ConsultasController::class,'profesiones'])->name('api_profesiones2');
    });


    Route::prefix('/clientes')->group(function(){
        Route::post('/actualizar-selfie-cedula',[ClienteController::class,'actualizarSelfieCedula']);
        Route::post('/actualizar-cedula-frente',[ClienteController::class,'actualizarCedulaFrente']);
        Route::post('/actualizar-cedula-dorso',[ClienteController::class,'actualizarCedulaDorso']);
    });



    Route::get('/sucursales-cercanas',[FarmaController::class,'sucursalesCercanas'])->name('api_sucursales_cercanas');

    Route::get('/mis-descuentos',[ClienteController::class,'misDescuentos'])->name('api_mis_descuentos');
    Route::get('/mis-adicionales',[ClienteController::class,'misAdicionales'])->name('api_mis_adicionales');
    Route::get('/mis-adicionales-tarjeta',[ClienteController::class,'tarjeta'])->name('api_mis_adicionales_tarjetas');

    Route::post('/vincular-vendedor',[VendedorController::class,'vincular'])->name('api_vincular_vendedor');

    Route::prefix('pdf')->group(function(){
        Route::get('/recibo',[PdfController::class,'recibo'])->name('api_recibos');
    });
});




Route::get('/salud',function(){
    return response()->json(['success'=>true,'message'=>'Salud optima']);
})->name('api_check_env');