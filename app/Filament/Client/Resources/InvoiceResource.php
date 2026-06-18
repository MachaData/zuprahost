<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Concerns\ScopedToClient;
use App\Filament\Client\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use ScopedToClient;

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'factura';

    protected static ?string $pluralModelLabel = 'mis facturas';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable(),
                Tables\Columns\TextColumn::make('issued_at')->label('Emisión')->date(),
                Tables\Columns\TextColumn::make('due_at')->label('Vence')->date()
                    ->color(fn ($state, Invoice $r) => $state && $state->isPast() && $r->status !== 'pagado' ? 'danger' : null),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('PEN'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'pagado', 'warning' => 'pendiente', 'danger' => 'vencido',
                    'gray' => 'anulado', 'info' => 'parcial',
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado',
                    'vencido' => 'Vencido', 'parcial' => 'Parcial',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('pagar')
                    ->label('Subir comprobante')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->color('success')
                    ->visible(fn (Invoice $r) => in_array($r->status, ['pendiente', 'parcial', 'vencido']))
                    ->form([
                        Forms\Components\TextInput::make('amount')->label('Monto pagado')->numeric()->prefix('S/')
                            ->default(fn (Invoice $r) => $r->total)->required(),
                        Forms\Components\Select::make('method')->label('Método de pago')->options([
                            'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia bancaria',
                            'efectivo' => 'Efectivo', 'otro' => 'Otro',
                        ])->default('yape')->required(),
                        Forms\Components\DatePicker::make('paid_at')->label('Fecha de pago')->default(now())->required(),
                        Forms\Components\TextInput::make('operation_code')->label('Código de operación'),
                        Forms\Components\FileUpload::make('receipt_path')->label('Imagen del comprobante')
                            ->image()->directory('receipts')->required(),
                    ])
                    ->action(function (Invoice $r, array $data) {
                        $r->payments()->create([
                            'client_id' => $r->client_id,
                            'amount' => $data['amount'],
                            'method' => $data['method'],
                            'paid_at' => $data['paid_at'],
                            'operation_code' => $data['operation_code'] ?? null,
                            'receipt_path' => $data['receipt_path'],
                            'status' => 'pendiente',
                        ]);
                        Notification::make()
                            ->title('Comprobante enviado')
                            ->body('Tu pago será validado por nuestro equipo.')
                            ->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
        ];
    }
}
