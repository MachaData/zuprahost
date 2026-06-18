<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hosting extends Model
{
    protected $fillable = [
        'client_id', 'domain_id', 'service_id', 'plan', 'server',
        'server_ip', 'directadmin_user', 'disk_space', 'bandwidth',
        'email_accounts', 'databases_limit', 'expires_at', 'status', 'notes',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
