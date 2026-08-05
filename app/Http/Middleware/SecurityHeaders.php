<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas del panel y del portal.
 *
 * No hay Content-Security-Policy: Filament y Livewire usan estilos y scripts
 * en línea, y una política a medias da falsa sensación de protección.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // El panel maneja datos de clientes y cobros: nunca dentro de un iframe
        // ajeno, que es como se montan los ataques de clickjacking.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Impide que el navegador "adivine" que un .txt subido es en realidad
        // un script y lo ejecute.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Al salir hacia otro sitio no se filtra la ruta completa, que puede
        // contener identificadores de facturas o clientes.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // Sin funciones del navegador que la aplicación no usa.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        );

        // HSTS solo con HTTPS real: enviarlo en local dejaría el navegador
        // forzando https://localhost durante meses.
        if ($request->secure() && app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }
}
