<?php

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceRenewedNotification extends Notification
{
    use Queueable;

    public function __construct(public Service $service) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Servicio renovado')
            ->greeting('Hola '.$this->service->client->name)
            ->line('Tu servicio "'.$this->service->name.'" fue renovado.')
            ->line('Nuevo vencimiento: '.optional($this->service->ends_at)->format('d/m/Y'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Servicio renovado',
            'message' => 'El servicio "'.$this->service->name.'" fue renovado hasta '.optional($this->service->ends_at)->format('d/m/Y').'.',
            'service_id' => $this->service->id,
        ];
    }
}
