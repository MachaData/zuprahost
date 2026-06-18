<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Ítems de la factura';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('service_id')
                ->label('Servicio (opcional)')
                ->relationship('service', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('description')
                ->label('Descripción')
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('quantity')
                ->label('Cantidad')
                ->numeric()
                ->default(1)
                ->required(),
            Forms\Components\TextInput::make('unit_price')
                ->label('Precio unitario')
                ->numeric()
                ->prefix('S/')
                ->default(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')->label('Descripción')->wrap(),
                Tables\Columns\TextColumn::make('quantity')->label('Cant.'),
                Tables\Columns\TextColumn::make('unit_price')->label('P. unit.')->money('PEN'),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('PEN'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(fn () => $this->recalc()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->after(fn () => $this->recalc()),
                Tables\Actions\DeleteAction::make()->after(fn () => $this->recalc()),
            ]);
    }

    protected function recalc(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->getOwnerRecord();
        $invoice->refresh()->recalculateTotals();
    }
}
