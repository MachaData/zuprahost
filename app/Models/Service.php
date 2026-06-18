<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'client_id', 'product_id', 'domain_id', 'name', 'price',
        'billing_cycle', 'starts_at', 'ends_at', 'next_renewal_at',
        'status', 'auto_renew', 'notes',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'next_renewal_at' => 'date',
        'auto_renew' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(ServiceRenewal::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
