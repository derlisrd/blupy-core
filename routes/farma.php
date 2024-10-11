<?php

use App\Http\Controllers\Farma\VendedoresController;
use Illuminate\Support\Facades\Route;

Route::get('/generar-qr',[VendedoresController::class,'generarQRVendedor'])->name('farma_generar_qr');
