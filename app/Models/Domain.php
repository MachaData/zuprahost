<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'client_id', 'name', 'provider', 'origin', 'renewal_managed',
        'registered_at', 'expires_at', 'renewal_price', 'cost', 'status',
        'nameservers', 'whois_protection', 'auto_renew', 'notes',
    ];

    protected $casts = [
        'registered_at' => 'date',
        'expires_at' => 'date',
        'renewal_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'whois_protection' => 'boolean',
        'auto_renew' => 'boolean',
        'renewal_managed' => 'boolean',
    ];

    /**
     * ¿Lo registramos nosotros o lo trajo el cliente de otro proveedor?
     */
    public function isExternal(): bool
    {
        return $this->origin === 'externo';
    }

    /**
     * Cómo se resume la situación del dominio en una sola frase. Son dos
     * cosas distintas: quién lo registró y quién lo renueva. Un dominio
     * externo puede pasar a renovarse con nosotros.
     */
    public function originLabel(): string
    {
        return match (true) {
            ! $this->isExternal() => 'Registrado con nosotros',
            $this->renewal_managed => 'Externo · lo renovamos nosotros',
            default => 'Externo · lo renueva el cliente',
        };
    }

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
