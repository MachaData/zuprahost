<?php

namespace App\Support;

class MailDelivery
{
    /**
     * ¿Hay un servidor de correo real detrás?
     *
     * De esto depende que el portal muestre «¿Olvidaste tu contraseña?». Con
     * MAIL_MAILER=log el enlace existiría pero el correo nunca llegaría: el
     * cliente se quedaría esperando en lugar de llamar al soporte.
     */
    public static function isConfigured(): bool
    {
        $mailer = config('mail.default');

        if (in_array($mailer, ['log', 'array', null], true)) {
            return false;
        }

        // El transporte smtp sin host no envía nada; los demás (ses, postmark,
        // resend…) se configuran con credenciales propias y se dan por buenos.
        if ($mailer === 'smtp') {
            return filled(config('mail.mailers.smtp.host'));
        }

        return true;
    }
}
