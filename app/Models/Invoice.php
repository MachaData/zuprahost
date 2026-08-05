<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id', 'code', 'issued_at', 'due_at', 'subtotal', 'tax',
        'total', 'status', 'payment_method', 'notes',
        'period_start', 'period_end', 'attachment_path', 'attachment_name',
        'document_type', 'series', 'number', 'sunat_status', 'sunat_response',
        'sunat_pdf_path', 'sunat_xml_path', 'sunat_cdr_path',
        'invoice_requested_at', 'billing_document_type', 'billing_document_number',
        'billing_name', 'billing_address', 'billing_email',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'invoice_requested_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'sunat_response' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recalculate subtotal/tax/total from the related items.
     *
     * Cambiar el total cambia el saldo, así que el estado se revisa después:
     * si no, agregar un ítem a una factura ya cobrada la dejaría marcada como
     * "pagado" con deuda pendiente.
     */
    public function recalculateTotals(float $taxRate = 0.18): void
    {
        $subtotal = (float) $this->items()->sum('total');
        $this->subtotal = round($subtotal, 2);
        $this->tax = round($subtotal * $taxRate, 2);
        $this->total = round($this->subtotal + $this->tax, 2);
        $this->saveQuietly();

        app(\App\Services\PaymentManager::class)->syncInvoiceStatus($this);
        $this->client?->syncDebtorStatus();
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->where('status', 'aprobado')->sum('amount');
    }

    public function isFullyPaid(): bool
    {
        return $this->amountPaid() >= (float) $this->total && (float) $this->total > 0;
    }

    /**
     * Lo que falta cobrar. Una factura anulada no debe nada.
     */
    public function balance(): float
    {
        if ($this->status === 'anulado') {
            return 0.0;
        }

        return round(max(0, (float) $this->total - $this->amountPaid()), 2);
    }

    /**
     * Facturas que todavía generan deuda.
     */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ['pendiente', 'parcial', 'vencido']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->whereDate('due_at', '<', today());
    }

    public function scopeSettled(Builder $query): Builder
    {
        return $query->whereIn('status', ['pagado', 'anulado']);
    }

    /**
     * ¿Ya se emitió el comprobante electrónico ante SUNAT?
     */
    public function isElectronicallyIssued(): bool
    {
        return $this->sunat_status === 'aceptado';
    }

    /**
     * Cómo se llama el comprobante en la interfaz y en el nombre del archivo.
     */
    public function documentLabel(): string
    {
        return match ($this->document_type) {
            'factura' => 'factura',
            'boleta' => 'boleta',
            default => 'nota-de-venta',
        };
    }

    /**
     * Serie y número si ya se emitió; si no, el código interno.
     */
    public function documentNumber(): string
    {
        return $this->series && $this->number
            ? "{$this->series}-{$this->number}"
            : $this->code;
    }

    /**
     * Periodo facturado en una frase: "Agosto 2026" cuando cae dentro del mes,
     * "01/08/2026 – 31/07/2027" cuando lo cruza.
     */
    public function periodLabel(): ?string
    {
        if (! $this->period_start) {
            return null;
        }

        $end = $this->period_end;

        if (! $end) {
            return $this->period_start->translatedFormat('d/m/Y');
        }

        if ($this->period_start->isSameMonth($end) && $this->period_start->isSameYear($end)) {
            return ucfirst($this->period_start->translatedFormat('F Y'));
        }

        return $this->period_start->format('d/m/Y').' – '.$end->format('d/m/Y');
    }

    /**
     * Enlaces de pago de los servicios que se están cobrando.
     *
     * El enlace no es un método del catálogo: se carga a mano en cada servicio
     * y solo aparece si está puesto. Sin enlace, no hay botón.
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, url: string}>
     */
    public function paymentLinks(): \Illuminate\Support\Collection
    {
        return $this->items()
            ->with('service')
            ->get()
            ->map(fn (InvoiceItem $item) => $item->service)
            ->filter(fn (?Service $service) => filled($service?->payment_link))
            ->unique('id')
            ->map(fn (Service $service) => [
                'name' => $service->name,
                'url' => $service->payment_link,
            ])
            ->values();
    }

    public function hasAttachment(): bool
    {
        return (bool) $this->attachment_path;
    }

    public function invoiceWasRequested(): bool
    {
        return $this->invoice_requested_at !== null;
    }

    /**
     * ¿Puede el cliente pedir factura por esta factura pagada?
     *
     * Se permite cuando está pagada, todavía es nota de venta, no se pidió
     * antes y al menos uno de los servicios facturados lo tiene habilitado
     * por el administrador.
     */
    public function canRequestElectronicInvoice(): bool
    {
        if ($this->status !== 'pagado' || $this->invoiceWasRequested()) {
            return false;
        }

        if ($this->document_type !== 'nota_venta') {
            return false;
        }

        return $this->items()
            ->whereHas('service', fn (Builder $q) => $q->where('allows_invoice_request', true))
            ->exists();
    }

    /**
     * Registra la solicitud con los datos fiscales que dio el cliente y deja
     * el tipo de comprobante que corresponde: con RUC es factura, si no boleta.
     */
    public function requestElectronicInvoice(array $data): void
    {
        $isCompany = ($data['billing_document_type'] ?? null) === 'ruc';

        $this->update([
            'invoice_requested_at' => now(),
            'document_type' => $isCompany ? 'factura' : 'boleta',
            'billing_document_type' => $data['billing_document_type'],
            'billing_document_number' => $data['billing_document_number'],
            'billing_name' => $data['billing_name'],
            'billing_address' => $data['billing_address'] ?? null,
            'billing_email' => $data['billing_email'] ?? null,
        ]);
    }

    /**
     * Fecha en que quedó saldada, tomada del último pago validado.
     */
    public function paidAt(): ?\Illuminate\Support\Carbon
    {
        return $this->payments()
            ->where('status', 'aprobado')
            ->orderByDesc('paid_at')
            ->value('paid_at');
    }

    public static function generateCode(): string
    {
        $year = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%s-%05d', $year, $count);
    }
}
