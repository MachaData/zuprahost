<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'user_id', 'type', 'name', 'document_type', 'document_number', 'wants_invoice',
        'email', 'phone', 'whatsapp', 'address', 'city', 'country',
        'status', 'notes', 'registered_at',
    ];

    protected $casts = [
        'wants_invoice' => 'boolean',
        'registered_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            $client->registered_at ??= now();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(Hosting::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function hasRuc(): bool
    {
        return $this->document_type === 'ruc' && ! empty($this->document_number);
    }

    /**
     * Tipo de comprobante por defecto según la elección del cliente:
     * - No desea facturación  → nota de venta (interna)
     * - Desea y tiene RUC     → factura
     * - Desea sin RUC         → boleta
     */
    public function defaultDocumentType(): string
    {
        if (! $this->wants_invoice) {
            return 'nota_venta';
        }

        return $this->hasRuc() ? 'factura' : 'boleta';
    }
}
