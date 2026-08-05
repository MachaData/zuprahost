<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\PaymentApprovedNotification;
use App\Notifications\PaymentRejectedNotification;

/**
 * Flujo de validación de comprobantes. El recálculo de la factura y del
 * estado del cliente lo dispara PaymentObserver al guardar el pago; aquí
 * solo viven la transición de estado y el aviso al cliente.
 */
class PaymentManager
{
    public function approve(Payment $payment): void
    {
        $payment->update(['status' => 'aprobado']);

        $payment->client->user?->notify(new PaymentApprovedNotification($payment));
    }

    /**
     * Rechaza un comprobante. Si ya estaba aprobado, el dinero deja de contar
     * y la factura vuelve por sí sola a pendiente, parcial o vencida.
     */
    public function reject(Payment $payment, ?string $reason = null): void
    {
        $payment->update([
            'status' => 'rechazado',
            'admin_note' => $reason ?? $payment->admin_note,
        ]);

        $payment->client->user?->notify(new PaymentRejectedNotification($payment));
    }

    /**
     * Registra un cobro hecho fuera del portal (efectivo, transferencia que
     * conciliamos nosotros…). Nace validado: lo ingresa el administrador, no
     * hay comprobante que revisar.
     *
     * Existe para que "dar por pagada" una factura siempre deje rastro en
     * pagos; sin esto el cliente vería la factura como pagada y con saldo a
     * la vez, porque su portal suma únicamente comprobantes aprobados.
     */
    public function register(Invoice $invoice, array $data): Payment
    {
        return $invoice->payments()->create([
            'client_id' => $invoice->client_id,
            'amount' => $data['amount'],
            'method' => $data['method'] ?? 'manual',
            'paid_at' => $data['paid_at'] ?? today(),
            'operation_code' => $data['operation_code'] ?? null,
            'admin_note' => $data['admin_note'] ?? null,
            'status' => 'aprobado',
        ]);
    }

    /**
     * Única fuente de verdad del estado de una factura frente a sus pagos.
     * Las anuladas no se tocan: anular es una decisión del administrador que
     * ningún cobro debe revertir.
     */
    public function syncInvoiceStatus(?Invoice $invoice): void
    {
        if (! $invoice || $invoice->status === 'anulado') {
            return;
        }

        $paid = $invoice->amountPaid();

        // "Vencido" pesa más que "parcial", igual que en el comando de
        // recordatorios; si no, ambos se pisarían el estado mutuamente.
        $status = match (true) {
            $invoice->isFullyPaid() => 'pagado',
            $invoice->due_at?->isPast() => 'vencido',
            $paid > 0 => 'parcial',
            default => 'pendiente',
        };

        if ($invoice->status !== $status) {
            $invoice->update(['status' => $status]);
        }
    }
}
