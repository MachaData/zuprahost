<?php

use App\Http\Controllers\PanelController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// Sitio público: los planes y los precios salen del catálogo real, así que
// una tarifa que se cambia en el panel se ve aquí sin tocar código.
Route::get('/', [SiteController::class, 'home'])->name('site.home');

// Contratar un servicio — Paso 1: elegir servicio
Route::view('/contratar', 'order.type')->name('order.create');
// Compatibilidad con el enlace anterior
Route::redirect('/constructor', '/contratar');

// Portal del cliente (vistas a medida)
Route::middleware('auth')->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/panel/servicios', [PanelController::class, 'services'])->name('panel.services');
    Route::get('/panel/servicios/{service}', [PanelController::class, 'service'])->name('panel.service');
    Route::get('/panel/dominios', [PanelController::class, 'domains'])->name('panel.domains');
    Route::get('/panel/pagos', [PanelController::class, 'payments'])->name('panel.payments');
    Route::get('/panel/pagos/{invoice}', [PanelController::class, 'payment'])->name('panel.payment');
    Route::get('/panel/pagos/{invoice}/recibo', [PanelController::class, 'receipt'])->name('panel.receipt');
    Route::get('/panel/pagos/{invoice}/comprobante', [PanelController::class, 'invoiceDocument'])->name('panel.invoice.document');
    Route::post('/panel/pagos/{invoice}/factura', [PanelController::class, 'requestInvoice'])->name('panel.invoice.request');
    Route::get('/panel/licencias', [PanelController::class, 'licenses'])->name('panel.licenses');
    Route::get('/panel/renovaciones', [PanelController::class, 'renewals'])->name('panel.renewals');

    // El enlace anterior seguía llamándose "facturas"
    Route::redirect('/panel/facturas', '/panel/pagos');
});
