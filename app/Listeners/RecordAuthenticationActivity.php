<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Deja constancia de quién entra al sistema y de los intentos fallidos.
 *
 * Es el mínimo indispensable para responder a "¿alguien más usó esta cuenta?"
 * sin montar un registro de auditoría completo.
 */
class RecordAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        // saveQuietly: actualizar el último acceso no es un cambio del usuario
        // y no debe disparar observadores ni tocar updated_at de forma visible.
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ])->saveQuietly();
    }

    public function handleFailed(Failed $event): void
    {
        Log::channel(config('logging.default'))->warning('Intento de acceso fallido', [
            'email' => $event->credentials['email'] ?? null,
            'ip' => request()->ip(),
            'guard' => $event->guard,
        ]);
    }
}
