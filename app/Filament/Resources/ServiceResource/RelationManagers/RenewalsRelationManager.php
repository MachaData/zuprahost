<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RenewalsRelationManager extends RelationManager
{
    protected static string $relationship = 'renewals';

    protected static ?string $title = 'Historial de renovaciones';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('new_ends_at')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime(),
                Tables\Columns\TextColumn::make('previous_ends_at')->label('Vencía')->date(),
                Tables\Columns\TextColumn::make('new_ends_at')->label('Nuevo vencimiento')->date(),
                Tables\Columns\TextColumn::make('amount')->label('Monto')->money('PEN'),
                Tables\Columns\TextColumn::make('user.name')->label('Realizado por'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
