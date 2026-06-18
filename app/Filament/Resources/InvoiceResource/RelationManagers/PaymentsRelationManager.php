<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos registrados';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('amount')->label('Monto')->numeric()->prefix('S/')->required(),
            Forms\Components\Select::make('method')->label('Método')->options([
                'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia',
                'efectivo' => 'Efectivo', 'manual' => 'Pago manual', 'otro' => 'Otro',
            ])->default('transferencia')->required(),
            Forms\Components\DatePicker::make('paid_at')->label('Fecha de pago')->default(now()),
            Forms\Components\TextInput::make('operation_code')->label('Código de operación'),
            Forms\Components\Select::make('status')->label('Estado')->options([
                'pendiente' => 'Pendiente de validación',
                'aprobado' => 'Aprobado',
                'rechazado' => 'Rechazado',
            ])->default('aprobado')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('operation_code')
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')->label('Fecha')->date(),
                Tables\Columns\TextColumn::make('amount')->label('Monto')->money('PEN'),
                Tables\Columns\TextColumn::make('method')->label('Método')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->colors(['success' => 'aprobado', 'warning' => 'pendiente', 'danger' => 'rechazado']),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['client_id'] = $this->getOwnerRecord()->client_id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
