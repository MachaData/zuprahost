<?php

namespace App\Filament\Client\Concerns;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restringe un Resource del portal al cliente autenticado.
 * El modelo debe tener una columna client_id.
 */
trait ScopedToClient
{
    public static function currentClientId(): ?int
    {
        return auth()->user()?->client?->id;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('client_id', static::currentClientId() ?? 0);
    }

    public static function currentClient(): ?Client
    {
        return auth()->user()?->client;
    }
}
