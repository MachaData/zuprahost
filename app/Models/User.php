<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    /**
     * ¿Es la última cuenta con rol Administrador?
     *
     * Se consulta antes de borrar o degradar a alguien: quedarse sin ningún
     * administrador deja el panel inaccesible y solo se arregla por consola.
     */
    public function isLastAdministrator(): bool
    {
        if (! $this->hasRole('Administrador')) {
            return false;
        }

        return static::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'Administrador'))
            ->whereKeyNot($this->getKey())
            ->doesntExist();
    }

    /**
     * The client profile linked to this user (for portal access).
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Route notifications for the WhatsApp channel (uses the client's number).
     */
    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->client?->whatsapp;
    }

    /**
     * Determine which Filament panels this user may access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasAnyRole(['Administrador', 'Soporte', 'Ventas', 'Facturación']);
        }

        // Client panel: any user that has a client profile or the Cliente role.
        return $this->hasRole('Cliente') || $this->client()->exists();
    }
}
