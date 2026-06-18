<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Concerns\ScopedToClient;
use App\Filament\Client\Resources\LicenseResource\Pages;
use App\Models\License;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    use ScopedToClient;

    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $modelLabel = 'licencia';

    protected static ?string $pluralModelLabel = 'mis licencias';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('license_key')->label('Clave')->copyable(),
                Tables\Columns\TextColumn::make('product.name')->label('Producto'),
                Tables\Columns\TextColumn::make('authorized_domain')->label('Dominio'),
                Tables\Columns\TextColumn::make('version')->label('Versión'),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activa', 'danger' => 'vencida',
                    'warning' => 'suspendida', 'gray' => 'cancelada',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
        ];
    }
}
