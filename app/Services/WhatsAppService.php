<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Envío de mensajes por WhatsApp Cloud API (Meta).
 *
 * Si la integración no está habilitada o faltan credenciales, el mensaje se
 * registra en el log en lugar de enviarse, para no bloquear el sistema.
 */
class WhatsAppService
{
    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.enabled')
            && filled(config('whatsapp.token'))
            && filled(config('whatsapp.phone_number_id'));
    }

    /**
     * Normaliza un número a formato internacional (solo dígitos).
     */
    public function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // Si no trae código de país (número local de 9 dígitos), se antepone.
        if (Str::length($digits) <= 9) {
            $digits = config('whatsapp.default_country_code').$digits;
        }

        return $digits;
    }

    public function send(string $to, string $message): bool
    {
        $to = $this->normalize($to);

        if (! $this->isEnabled()) {
            Log::info('[WhatsApp simulado] '.$to.': '.$message);

            return false;
        }

        $response = Http::withToken(config('whatsapp.token'))
            ->acceptJson()
            ->post(rtrim(config('whatsapp.base_url'), '/').'/'.config('whatsapp.phone_number_id').'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if (! $response->successful()) {
            Log::warning('[WhatsApp error] '.$response->body());

            return false;
        }

        return true;
    }
}
