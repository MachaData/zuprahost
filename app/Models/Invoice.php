<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id', 'code', 'issued_at', 'due_at', 'subtotal', 'tax',
        'total', 'status', 'payment_method', 'notes',
        'document_type', 'series', 'number', 'sunat_status', 'sunat_response',
        'sunat_pdf_path', 'sunat_xml_path', 'sunat_cdr_path',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
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
     */
    public function recalculateTotals(float $taxRate = 0.18): void
    {
        $subtotal = (float) $this->items()->sum('total');
        $this->subtotal = round($subtotal, 2);
        $this->tax = round($subtotal * $taxRate, 2);
        $this->total = round($this->subtotal + $this->tax, 2);
        $this->saveQuietly();
    }

    public function amountPaid(): float
    {
        return (float) $this->payments()->where('status', 'aprobado')->sum('amount');
    }

    public function isFullyPaid(): bool
    {
        return $this->amountPaid() >= (float) $this->total && (float) $this->total > 0;
    }

    public static function generateCode(): string
    {
        $year = now()->format('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('INV-%s-%05d', $year, $count);
    }
}
