<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notificaciones por WhatsApp
    |--------------------------------------------------------------------------
    |
    | Integración opcional para enviar avisos por WhatsApp usando la
    | WhatsApp Cloud API de Meta. Mientras 'enabled' sea false (o falte el
    | token) los mensajes se registran en el log pero NO se envían, por lo que
    | el resto del sistema funciona sin necesidad de credenciales.
    |
    */

    'enabled' => (bool) env('WHATSAPP_ENABLED', false),

    // WhatsApp Cloud API (Meta)
    'token' => env('WHATSAPP_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com/v21.0'),

    // Código de país por defecto para normalizar números (Perú = 51).
    'default_country_code' => env('WHATSAPP_COUNTRY_CODE', '51'),
];
