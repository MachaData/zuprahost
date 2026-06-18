<?php

namespace App\Filament\Resources\TicketResource\RelationManagers;

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
                ->label('Mensaje')
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
                Tables\Columns\IconColumn::make('is_staff')->label('Staff')->boolean(),
                Tables\Columns\TextColumn::make('message')->label('Mensaje')->wrap()->limit(120),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->since(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Responder')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        $data['is_staff'] = true;

                        return $data;
                    }),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
