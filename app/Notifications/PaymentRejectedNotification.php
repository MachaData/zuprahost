<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

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
        return '⚠️ Tu pago por S/ '.number_format((float) $this->payment->amount, 2).' fue rechazado.'
            .($this->payment->admin_note ? ' Motivo: '.$this->payment->admin_note : '');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago rechazado')
            ->greeting('Hola '.$this->payment->client->name)
            ->line('Tu pago por S/ '.number_format((float) $this->payment->amount, 2).' fue rechazado.')
            ->when($this->payment->admin_note, fn ($m) => $m->line('Motivo: '.$this->payment->admin_note))
            ->line('Por favor verifica los datos y vuelve a intentarlo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pago rechazado',
            'message' => 'Tu pago fue rechazado. '.($this->payment->admin_note ?? ''),
            'payment_id' => $this->payment->id,
        ];
    }
}
