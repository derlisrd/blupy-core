<?php

use App\Http\Controllers\Farma\VendedoresController;
use Illuminate\Support\Facades\Route;

Route::get('/generar-qr',[VendedoresController::class,'generarQRVendedor'])->name('farma_generar_qr');
Route::post('/ingresar-vendedor',[VendedoresController::class,'ingresarVendedor'])->name('farma_ingresar_vendedor');
