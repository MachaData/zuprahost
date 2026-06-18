<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'client_id', 'name', 'provider', 'registered_at', 'expires_at',
        'renewal_price', 'cost', 'status', 'nameservers',
        'whois_protection', 'auto_renew', 'notes',
    ];

    protected $casts = [
        'registered_at' => 'date',
        'expires_at' => 'date',
        'renewal_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'whois_protection' => 'boolean',
        'auto_renew' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(Hosting::class);
    }
}
