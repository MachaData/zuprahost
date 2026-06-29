<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovedNotification extends Notification
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
        return '✅ Tu pago por S/ '.number_format((float) $this->payment->amount, 2).' fue aprobado. ¡Gracias!';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago aprobado')
            ->greeting('Hola '.$this->payment->client->name)
            ->line('Tu pago por S/ '.number_format((float) $this->payment->amount, 2).' ha sido aprobado.')
            ->line('Gracias por tu confianza.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pago aprobado',
            'message' => 'Tu pago por S/ '.number_format((float) $this->payment->amount, 2).' fue aprobado.',
            'payment_id' => $this->payment->id,
        ];
    }
}
