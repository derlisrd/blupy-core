<?php

use App\Http\Controllers\Farma\VendedoresController;
use Illuminate\Support\Facades\Route;

Route::get('/generar-qr',[VendedoresController::class,'generarQRVendedor'])->name('farma_generar_qr');
Route::post('/registrar-vendedor-qr',[VendedoresController::class,'registrarVendedorQr'])->name('farma_ingresar_vendedor');


Route::get('/activaciones',[VendedoresController::class,'activaciones']);
