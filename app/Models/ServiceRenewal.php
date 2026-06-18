<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRenewal extends Model
{
    protected $fillable = [
        'service_id', 'invoice_id', 'user_id',
        'previous_ends_at', 'new_ends_at', 'amount', 'notes',
    ];

    protected $casts = [
        'previous_ends_at' => 'date',
        'new_ends_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
