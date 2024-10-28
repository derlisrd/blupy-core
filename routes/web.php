<?php
use Illuminate\Support\Facades\Route;

Route::get('/terminos', function () {
    return view('terminos');
});
Route::get('/terminos2', function () {
    return view('terminos');
});
Route::get('/cumple', function () {
    return view('email.cumpleanios');
});
