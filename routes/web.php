<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Constructor de curso — Paso 1: tipo de contenido
Route::view('/constructor', 'builder.type')->name('builder.type');
