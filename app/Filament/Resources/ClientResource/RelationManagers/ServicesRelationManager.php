<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Servicios contratados';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre del servicio')->required(),
            Forms\Components\TextInput::make('price')->label('Precio')->numeric()->prefix('S/')->default(0),
            Forms\Components\Select::make('billing_cycle')->label('Ciclo')->options([
                'mensual' => 'Mensual', 'anual' => 'Anual', 'unico' => 'Pago único',
            ])->default('mensual'),
            Forms\Components\Select::make('status')->label('Estado')->options([
                'pendiente' => 'Pendiente', 'activo' => 'Activo', 'suspendido' => 'Suspendido',
                'cancelado' => 'Cancelado', 'vencido' => 'Vencido',
            ])->default('pendiente'),
            Forms\Components\DatePicker::make('ends_at')->label('Vencimiento'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Servicio'),
                Tables\Columns\TextColumn::make('price')->label('Precio')->money('PEN'),
                Tables\Columns\TextColumn::make('billing_cycle')->label('Ciclo')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
                Tables\Columns\TextColumn::make('ends_at')->label('Vence')->date(),
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
