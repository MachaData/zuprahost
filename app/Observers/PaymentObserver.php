<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\PaymentManager;

/**
 * Mantiene coherentes la factura y el estado del cliente ante cualquier
 * cambio en los pagos, venga de donde venga: acciones del panel, edición
 * manual del formulario, borrado en lote o un script de consola.
 */
class PaymentObserver
{
    public function __construct(protected PaymentManager $payments) {}

    public function saved(Payment $payment): void
    {
        $this->resync($payment);
    }

    public function deleted(Payment $payment): void
    {
        $this->resync($payment);
    }

    protected function resync(Payment $payment): void
    {
        $this->payments->syncInvoiceStatus($payment->invoice);
        $payment->client?->syncDebtorStatus();
    }
}
