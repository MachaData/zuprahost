<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Services\ApisPeruService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $modelLabel = 'factura';

    protected static ?string $pluralModelLabel = 'facturas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la factura')->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('code')->label('Código')
                    ->default(fn () => Invoice::generateCode())
                    ->required()->unique(ignoreRecord: true),
                Forms\Components\DatePicker::make('issued_at')->label('Fecha de emisión')->default(now()),
                Forms\Components\DatePicker::make('due_at')->label('Fecha de vencimiento')->default(now()->addDays(7)),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'vencido' => 'Vencido',
                    'anulado' => 'Anulado', 'parcial' => 'Parcial',
                ])->default('pendiente')->required(),
                Forms\Components\TextInput::make('payment_method')->label('Método de pago'),
            ]),
            Forms\Components\Section::make('Comprobante electrónico (SUNAT / APISPERU)')
                ->columns(3)
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('document_type')->label('Tipo de comprobante')->options([
                        'nota_venta' => 'Nota de venta (interno)',
                        'boleta' => 'Boleta',
                        'factura' => 'Factura',
                    ])->default('nota_venta'),
                    Forms\Components\TextInput::make('series')->label('Serie')->placeholder('F001 / B001'),
                    Forms\Components\TextInput::make('number')->label('Número'),
                    Forms\Components\Placeholder::make('sunat_status_label')
                        ->label('Estado SUNAT')
                        ->content(fn (?Invoice $record) => $record?->sunat_status ?? 'no_enviado'),
                ]),
            Forms\Components\Section::make('Totales')->columns(3)->schema([
                Forms\Components\TextInput::make('subtotal')->label('Subtotal')->numeric()->prefix('S/')->default(0)
                    ->helperText('Se recalcula automáticamente desde los ítems.'),
                Forms\Components\TextInput::make('tax')->label('IGV (18%)')->numeric()->prefix('S/')->default(0),
                Forms\Components\TextInput::make('total')->label('Total')->numeric()->prefix('S/')->default(0),
            ]),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('issued_at')->label('Emisión')->date()->sortable(),
                Tables\Columns\TextColumn::make('due_at')->label('Vence')->date()->sortable()
                    ->color(fn ($state, Invoice $r) => $state && $state->isPast() && $r->status !== 'pagado' ? 'danger' : null),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('PEN')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'pagado', 'warning' => 'pendiente', 'danger' => 'vencido',
                    'gray' => 'anulado', 'info' => 'parcial',
                ]),
                Tables\Columns\BadgeColumn::make('sunat_status')->label('SUNAT')->toggleable()->colors([
                    'gray' => 'no_enviado', 'info' => 'enviado',
                    'success' => 'aceptado', 'danger' => 'rechazado',
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'vencido' => 'Vencido',
                    'anulado' => 'Anulado', 'parcial' => 'Parcial',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('marcar_pagada')
                    ->label('Marcar pagada')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $r) => $r->status !== 'pagado' && $r->status !== 'anulado')
                    ->requiresConfirmation()
                    ->action(fn (Invoice $r) => $r->update(['status' => 'pagado'])),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Invoice $r) => $r->status !== 'anulado')
                    ->requiresConfirmation()
                    ->action(fn (Invoice $r) => $r->update(['status' => 'anulado'])),
                Tables\Actions\Action::make('enviar_sunat')
                    ->label('Emitir SUNAT')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info')
                    ->visible(fn (Invoice $r) => in_array($r->document_type, ['boleta', 'factura']) && $r->sunat_status === 'no_enviado')
                    ->requiresConfirmation()
                    ->action(function (Invoice $r) {
                        try {
                            app(ApisPeruService::class)->send($r);
                            Notification::make()->title('Comprobante enviado a SUNAT')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Error al emitir')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
