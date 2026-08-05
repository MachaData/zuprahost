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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
