<?php

namespace App\Http\Controllers;

use App\Support\Catalog;
use Illuminate\View\View;

/**
 * Sitio público de zupraHost.
 *
 * Todo lo que tiene precio sale del catálogo real, así que subir una tarifa
 * en el panel actualiza la portada sin tocar código.
 */
class SiteController extends Controller
{
    public function home(): View
    {
        return view('site.home', [
            'services' => Catalog::services(),
            'plansByCategory' => Catalog::plansByCategory(),
            'domains' => Catalog::domains(),
        ]);
    }
}
