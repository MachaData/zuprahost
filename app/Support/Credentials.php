<?php

namespace App\Support;

use Illuminate\Support\Str;

class Credentials
{
    /**
     * Contraseña temporal que cumple la política de la aplicación.
     *
     * Str::password() sortea caracteres al azar, así que puede devolver una
     * sin mayúsculas o sin símbolos y el formulario la rechazaría delante del
     * cliente. Se reintenta hasta que cumple.
     */
    public static function password(int $length = 16): string
    {
        do {
            $password = Str::password($length);
        } while (! static::satisfiesPolicy($password));

        return $password;
    }

    public static function satisfiesPolicy(string $password): bool
    {
        return mb_strlen($password) >= 12
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^\p{L}\p{N}]/u', $password);
    }
}
