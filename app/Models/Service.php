<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    protected $fillable = [
        'client_id', 'product_id', 'domain_id', 'name', 'price',
        'billing_cycle', 'starts_at', 'ends_at', 'next_renewal_at',
        'status', 'auto_renew', 'notes', 'allows_invoice_request', 'payment_link',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'next_renewal_at' => 'date',
        'auto_renew' => 'boolean',
        'allows_invoice_request' => 'boolean',
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

    /**
     * Datos de alojamiento del servicio (servidor, IP, usuario de panel…).
     * Es lo que el cliente ve como "accesos" en el detalle del servicio.
     */
    public function hosting(): HasOne
    {
        return $this->hasOne(Hosting::class);
    }

    /**
     * Enlaces de acceso del servicio (panel, webmail, consola del VPS…).
     */
    public function accesses(): HasMany
    {
        return $this->hasMany(ServiceAccess::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * Solo los que el cliente puede ver en su portal.
     */
    public function visibleAccesses(): HasMany
    {
        return $this->accesses()->where('is_visible', true);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
