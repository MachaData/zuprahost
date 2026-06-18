<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Facturas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código'),
                Tables\Columns\TextColumn::make('issued_at')->label('Emisión')->date(),
                Tables\Columns\TextColumn::make('due_at')->label('Vence')->date(),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('PEN'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->colors([
                        'success' => 'pagado',
                        'warning' => 'pendiente',
                        'danger' => 'vencido',
                        'gray' => 'anulado',
                        'info' => 'parcial',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => \App\Filament\Resources\InvoiceResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
