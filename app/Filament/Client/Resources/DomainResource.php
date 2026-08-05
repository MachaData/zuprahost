<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Concerns\ScopedToClient;
use App\Filament\Client\Resources\DomainResource\Pages;
use App\Models\Domain;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DomainResource extends Resource
{
    use ScopedToClient;

    protected static ?string $model = Domain::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $modelLabel = 'dominio';

    protected static ?string $pluralModelLabel = 'mis dominios';

    protected static ?int $navigationSort = 2;

    // La navegación la sirve <x-portal-shell> vía /panel/*; aquí solo
    // se conserva la ruta (la usa la acción de subir comprobante).
    protected static bool $shouldRegisterNavigation = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Dominio')->searchable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('nameservers')->label('Nameservers')->limit(40)->toggleable(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activo', 'warning' => 'por_vencer',
                    'danger' => 'vencido', 'gray' => 'suspendido',
                ]),
            ])
            ->defaultSort('expires_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomains::route('/'),
        ];
    }
}
