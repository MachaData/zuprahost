<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = 'Dominios';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Dominio')->required(),
            Forms\Components\TextInput::make('provider')->label('Proveedor'),
            Forms\Components\DatePicker::make('expires_at')->label('Vencimiento'),
            Forms\Components\Select::make('status')->label('Estado')->options([
                'activo' => 'Activo', 'por_vencer' => 'Por vencer',
                'vencido' => 'Vencido', 'suspendido' => 'Suspendido',
            ])->default('activo'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Dominio'),
                Tables\Columns\TextColumn::make('provider')->label('Proveedor'),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
