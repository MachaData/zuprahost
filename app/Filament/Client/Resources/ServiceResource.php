<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Concerns\ScopedToClient;
use App\Filament\Client\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use ScopedToClient;

    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $modelLabel = 'servicio';

    protected static ?string $pluralModelLabel = 'mis servicios';

    protected static ?int $navigationSort = 1;

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
                Tables\Columns\TextColumn::make('name')->label('Servicio')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Precio')->money('PEN'),
                Tables\Columns\TextColumn::make('billing_cycle')->label('Ciclo')->badge(),
                Tables\Columns\TextColumn::make('domain.name')->label('Dominio'),
                Tables\Columns\TextColumn::make('ends_at')->label('Vence')->date()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activo', 'gray' => ['pendiente', 'cancelado'],
                    'warning' => 'suspendido', 'danger' => 'vencido',
                ]),
            ])
            ->defaultSort('ends_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
        ];
    }
}
