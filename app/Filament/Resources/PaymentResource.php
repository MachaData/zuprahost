<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Services\PaymentManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $modelLabel = 'pago';

    protected static ?string $pluralModelLabel = 'pagos';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pendiente')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('invoice_id')->label('Factura')
                    ->relationship('invoice', 'code')->searchable()->preload(),
                Forms\Components\TextInput::make('amount')->label('Monto pagado')->numeric()->prefix('S/')->required(),
                Forms\Components\Select::make('method')->label('Método de pago')->options([
                    'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia bancaria',
                    'efectivo' => 'Efectivo', 'manual' => 'Pago manual', 'otro' => 'Otro',
                ])->default('transferencia')->required(),
                Forms\Components\DatePicker::make('paid_at')->label('Fecha de pago')->default(now()),
                Forms\Components\TextInput::make('operation_code')->label('Código de operación'),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente de validación',
                    'aprobado' => 'Aprobado',
                    'rechazado' => 'Rechazado',
                ])->default('pendiente')->required(),
                Forms\Components\FileUpload::make('receipt_path')->label('Comprobante')
                    ->image()->directory('receipts')->columnSpanFull(),
                Forms\Components\Textarea::make('admin_note')->label('Observación del administrador')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('invoice.code')->label('Factura')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('Monto')->money('PEN')->sortable(),
                Tables\Columns\TextColumn::make('method')->label('Método')->badge(),
                Tables\Columns\TextColumn::make('paid_at')->label('Fecha')->date()->sortable(),
                Tables\Columns\ImageColumn::make('receipt_path')->label('Comprobante')->toggleable(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'aprobado', 'warning' => 'pendiente', 'danger' => 'rechazado',
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Payment $r) => $r->status !== 'aprobado')
                    ->requiresConfirmation()
                    ->action(function (Payment $r) {
                        app(PaymentManager::class)->approve($r);
                        Notification::make()->title('Pago aprobado')->success()->send();
                    }),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (Payment $r) => $r->status !== 'rechazado')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')->label('Motivo')->required(),
                    ])
                    ->action(function (Payment $r, array $data) {
                        app(PaymentManager::class)->reject($r, $data['admin_note']);
                        Notification::make()->title('Pago rechazado')->warning()->send();
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
