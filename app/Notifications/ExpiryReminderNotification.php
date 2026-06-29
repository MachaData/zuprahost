<?php

namespace App\Notifications;

use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso genérico de vencimiento usado para servicios, dominios y facturas.
 */
class ExpiryReminderNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $kind  servicio|dominio|factura
     */
    public function __construct(
        public string $kind,
        public string $name,
        public ?string $date,
        public int $daysOffset,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (config('whatsapp.enabled')) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    public function toWhatsApp(object $notifiable): string
    {
        $when = $this->daysOffset > 0
            ? "vence en {$this->daysOffset} días"
            : ($this->daysOffset === 0 ? 'vence hoy' : 'venció hace '.abs($this->daysOffset).' días');

        return "🔔 Recordatorio: tu {$this->kind} \"{$this->name}\" {$when}"
            .($this->date ? " ({$this->date})" : '').'. Ingresa a tu panel para renovar.';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->daysOffset > 0
            ? "vence en {$this->daysOffset} días"
            : ($this->daysOffset === 0 ? 'vence hoy' : 'venció hace '.abs($this->daysOffset).' días');

        return (new MailMessage)
            ->subject('Recordatorio de vencimiento')
            ->line(ucfirst($this->kind).' "'.$this->name.'" '.$when.'.')
            ->when($this->date, fn ($m) => $m->line('Fecha: '.$this->date))
            ->line('Ingresa a tu panel para renovar.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Recordatorio de vencimiento',
            'message' => ucfirst($this->kind).' "'.$this->name.'" vence el '.$this->date.'.',
            'kind' => $this->kind,
            'days_offset' => $this->daysOffset,
        ];
    }
}
