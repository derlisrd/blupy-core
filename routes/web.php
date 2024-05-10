<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\XapiKeyTokenIsValid;


Route::get('/', function () {
    return view('welcome');
});
