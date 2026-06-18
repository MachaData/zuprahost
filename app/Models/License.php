<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class License extends Model
{
    protected $fillable = [
        'client_id', 'product_id', 'license_key', 'authorized_domain',
        'activated_at', 'expires_at', 'status', 'max_activations',
        'used_activations', 'version', 'download_url', 'notes',
    ];

    protected $casts = [
        'activated_at' => 'date',
        'expires_at' => 'date',
        'max_activations' => 'integer',
        'used_activations' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function generateKey(): string
    {
        return strtoupper(implode('-', str_split(Str::random(20), 5)));
    }
}
