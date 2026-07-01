<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Contratar un servicio — Paso 1: elegir servicio
Route::view('/contratar', 'order.type')->name('order.create');
// Compatibilidad con el enlace anterior
Route::redirect('/constructor', '/contratar');
