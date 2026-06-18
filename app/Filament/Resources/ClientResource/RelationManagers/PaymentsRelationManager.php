<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('operation_code')
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')->label('Fecha')->date(),
                Tables\Columns\TextColumn::make('amount')->label('Monto')->money('PEN'),
                Tables\Columns\TextColumn::make('method')->label('Método')->badge(),
                Tables\Columns\TextColumn::make('operation_code')->label('Operación'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->colors([
                        'success' => 'aprobado',
                        'warning' => 'pendiente',
                        'danger' => 'rechazado',
                    ]),
            ]);
    }
}
