<?php

namespace App\Filament\Client\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'replies';

    protected static ?string $title = 'Conversación';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('message')
                ->label('Tu respuesta')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('attachments')
                ->label('Adjuntos')
                ->multiple()
                ->directory('ticket-attachments')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Autor'),
                Tables\Columns\IconColumn::make('is_staff')->label('Soporte')->boolean(),
                Tables\Columns\TextColumn::make('message')->label('Mensaje')->wrap(),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Responder')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        $data['is_staff'] = false;

                        return $data;
                    })
                    ->after(function () {
                        // Reabrir el ticket cuando el cliente responde.
                        $this->getOwnerRecord()->update(['status' => 'abierto']);
                    }),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
