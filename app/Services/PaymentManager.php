<?php

namespace App\Services;

use App\Models\Payment;
use App\Notifications\PaymentApprovedNotification;
use App\Notifications\PaymentRejectedNotification;
use Illuminate\Support\Facades\DB;

class PaymentManager
{
    /**
     * Approve a payment. If it covers the related invoice, mark it paid;
     * otherwise mark the invoice as partial.
     */
    public function approve(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'aprobado']);

            if ($invoice = $payment->invoice) {
                if ($invoice->isFullyPaid()) {
                    $invoice->update(['status' => 'pagado']);
                } elseif ($invoice->amountPaid() > 0) {
                    $invoice->update(['status' => 'parcial']);
                }
            }
        });

        $payment->client->user?->notify(new PaymentApprovedNotification($payment));
    }

    public function reject(Payment $payment, ?string $reason = null): void
    {
        $payment->update([
            'status' => 'rechazado',
            'admin_note' => $reason ?? $payment->admin_note,
        ]);

        $payment->client->user?->notify(new PaymentRejectedNotification($payment));
    }
}
