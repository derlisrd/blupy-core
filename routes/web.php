<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('/app', function (Request $request) {
    $userAgent = $request->header('User-Agent');

    // Detectar Android
    if (stripos($userAgent, 'Android') !== false) {
        return redirect()->away('intent://blupy/#Intent;scheme=blupy;package=com.teo.blupy;end');
    }
    // Detectar iOS
    elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
        // Intentar abrir la app primero
        return response()->view('redirect_to_ios');
    }
    // Para otros dispositivos, redirigir al sitio web
    else {
        return redirect()->away('https://blupy.com.py');
    }
});
