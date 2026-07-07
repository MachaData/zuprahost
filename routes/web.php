<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Contratar un servicio — Paso 1: elegir servicio
Route::view('/contratar', 'order.type')->name('order.create');
// Compatibilidad con el enlace anterior
Route::redirect('/constructor', '/contratar');

// Panel del cliente (dashboard a medida)
Route::get('/panel', [\App\Http\Controllers\PanelController::class, 'index'])
    ->middleware('auth')
    ->name('panel');
