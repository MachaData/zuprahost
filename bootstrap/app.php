<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // El portal no tiene una ruta "login" propia: la autenticación de
        // clientes la sirve el panel Filament. Sin esto, /panel revienta con
        // "Route [login] not defined" para visitantes sin sesión.
        $middleware->redirectGuestsTo(fn () => route('filament.client.auth.login'));

        // Detrás del proxy de Railway la aplicación recibe las peticiones por
        // HTTP; sin confiar en las cabeceras X-Forwarded-*, Laravel generaría
        // enlaces http:// dentro de una página https:// y el navegador
        // bloquearía los recursos de Filament.
        $middleware->trustProxies(at: '*');

        // Global, no en el grupo `web`: los paneles de Filament registran sus
        // rutas con su propia pila de middleware y se saltarían el grupo.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
