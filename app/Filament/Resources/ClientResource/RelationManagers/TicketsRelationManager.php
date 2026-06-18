<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Tickets';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código'),
                Tables\Columns\TextColumn::make('subject')->label('Asunto'),
                Tables\Columns\TextColumn::make('priority')->label('Prioridad')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->colors([
                        'info' => 'abierto',
                        'warning' => ['en_proceso', 'esperando_cliente'],
                        'success' => 'resuelto',
                        'gray' => 'cerrado',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => \App\Filament\Resources\TicketResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
