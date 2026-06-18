<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Service;
use App\Notifications\ExpiryReminderNotification;
use Illuminate\Console\Command;

class ProcessBillingReminders extends Command
{
    protected $signature = 'app:process-billing-reminders';

    protected $description = 'Marca vencimientos y envía recordatorios de servicios, dominios y facturas.';

    /**
     * Días relativos al vencimiento en los que se notifica.
     */
    protected array $offsets = [7, 3, 0, -3, -7];

    public function handle(): int
    {
        $this->markOverdueInvoices();
        $this->markExpiredServices();
        $this->markDomainStatuses();
        $this->sendReminders();

        $this->info('Recordatorios y vencimientos procesados.');

        return self::SUCCESS;
    }

    protected function markOverdueInvoices(): void
    {
        Invoice::whereIn('status', ['pendiente', 'parcial'])
            ->whereDate('due_at', '<', today())
            ->update(['status' => 'vencido']);
    }

    protected function markExpiredServices(): void
    {
        Service::where('status', 'activo')
            ->whereDate('ends_at', '<', today())
            ->update(['status' => 'vencido']);
    }

    protected function markDomainStatuses(): void
    {
        Domain::where('status', 'activo')
            ->whereDate('expires_at', '<', today())
            ->update(['status' => 'vencido']);

        Domain::where('status', 'activo')
            ->whereDate('expires_at', '>=', today())
            ->whereDate('expires_at', '<=', today()->addDays(30))
            ->update(['status' => 'por_vencer']);
    }

    protected function sendReminders(): void
    {
        foreach ($this->offsets as $offset) {
            $target = today()->addDays($offset);

            Service::with('client.user')->whereDate('ends_at', $target)->get()
                ->each(fn (Service $s) => $s->client->user?->notify(
                    new ExpiryReminderNotification('servicio', $s->name, $s->ends_at?->format('d/m/Y'), $offset)
                ));

            Domain::with('client.user')->whereDate('expires_at', $target)->get()
                ->each(fn (Domain $d) => $d->client->user?->notify(
                    new ExpiryReminderNotification('dominio', $d->name, $d->expires_at?->format('d/m/Y'), $offset)
                ));

            Invoice::with('client.user')->whereIn('status', ['pendiente', 'parcial', 'vencido'])
                ->whereDate('due_at', $target)->get()
                ->each(fn (Invoice $i) => $i->client->user?->notify(
                    new ExpiryReminderNotification('factura', $i->code, $i->due_at?->format('d/m/Y'), $offset)
                ));
        }
    }
}
