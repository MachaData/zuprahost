<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Red de seguridad para las cuentas del panel.
 *
 * La comprobación vive en el modelo y no solo en el recurso de Filament
 * porque el borrado masivo, tinker y cualquier comando futuro pasan por aquí
 * y no por la interfaz.
 */
class UserObserver
{
    public function deleting(User $user): void
    {
        if ($user->isLastAdministrator()) {
            throw ValidationException::withMessages([
                'user' => 'No se puede eliminar al único administrador: el panel quedaría sin acceso.',
            ]);
        }
    }

    public function updating(User $user): void
    {
        // Una contraseña nueva reinicia el contador, venga de donde venga.
        if ($user->isDirty('password') && ! $user->isDirty('password_changed_at')) {
            $user->password_changed_at = now();
        }
    }
}
