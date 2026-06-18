<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Service;
use App\Models\ServiceRenewal;
use App\Notifications\ServiceRenewedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceManager
{
    /**
     * Suggest the next end date based on the billing cycle.
     */
    public function suggestNextEnd(Service $service): Carbon
    {
        $base = $service->ends_at && $service->ends_at->isFuture()
            ? $service->ends_at->copy()
            : now();

        return match ($service->billing_cycle) {
            'anual' => $base->addYear(),
            'unico' => $base->addYear(),
            default => $base->addMonth(),
        };
    }

    /**
     * Renew a service, record the renewal, and optionally generate an invoice.
     */
    public function renew(Service $service, Carbon $newEnd, float $amount, bool $generateInvoice = false): ServiceRenewal
    {
        return DB::transaction(function () use ($service, $newEnd, $amount, $generateInvoice) {
            $previous = $service->ends_at;

            $invoice = null;
            if ($generateInvoice && $amount > 0) {
                $invoice = $this->createRenewalInvoice($service, $amount);
            }

            $renewal = $service->renewals()->create([
                'invoice_id' => $invoice?->id,
                'user_id' => auth()->id(),
                'previous_ends_at' => $previous,
                'new_ends_at' => $newEnd,
                'amount' => $amount,
            ]);

            $service->update([
                'ends_at' => $newEnd,
                'next_renewal_at' => $newEnd,
                'status' => 'activo',
            ]);

            $service->client->user?->notify(new ServiceRenewedNotification($service));

            return $renewal;
        });
    }

    public function suspend(Service $service): void
    {
        $service->update(['status' => 'suspendido']);
    }

    public function cancel(Service $service): void
    {
        $service->update(['status' => 'cancelado']);
    }

    protected function createRenewalInvoice(Service $service, float $amount): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $service->client_id,
            'code' => Invoice::generateCode(),
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
            'status' => 'pendiente',
        ]);

        $invoice->items()->create([
            'service_id' => $service->id,
            'description' => 'Renovación: '.$service->name,
            'quantity' => 1,
            'unit_price' => $amount,
        ]);

        $invoice->recalculateTotals();

        return $invoice;
    }
}
