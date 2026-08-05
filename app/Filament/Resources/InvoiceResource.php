<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Services\ApisPeruService;
use App\Services\PaymentManager;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'invoice';
    }

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $modelLabel = 'factura';

    protected static ?string $pluralModelLabel = 'facturas';

    protected static ?int $navigationSort = 1;

    /**
     * Facturas que el cliente pidió y que todavía no se emiten: son trabajo
     * pendiente del administrador.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereNotNull('invoice_requested_at')
            ->where('sunat_status', '!=', 'aceptado')
            ->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

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
                Forms\Components\DatePicker::make('period_start')->label('Periodo facturado · desde')
                    ->helperText('Tramo que cubre el cobro. Se emite antes de que empiece.')
                    ->live(),
                Forms\Components\DatePicker::make('period_end')->label('Periodo facturado · hasta')
                    ->afterOrEqual('period_start'),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'vencido' => 'Vencido',
                    'anulado' => 'Anulado', 'parcial' => 'Parcial',
                ])->default('pendiente')->required(),
                Forms\Components\TextInput::make('payment_method')->label('Método de pago'),
            ]),
            // Datos con los que el cliente pidió su comprobante. Se muestran
            // como referencia y no se editan aquí: son los que él declaró.
            Forms\Components\Section::make('Factura solicitada por el cliente')
                ->columns(3)
                ->visible(fn (?Invoice $record) => $record?->invoiceWasRequested())
                ->schema([
                    Forms\Components\Placeholder::make('requested_at')->label('Solicitada el')
                        ->content(fn (Invoice $record) => $record->invoice_requested_at?->format('d/m/Y H:i')),
                    Forms\Components\Placeholder::make('requested_doc')->label('Documento')
                        ->content(fn (Invoice $record) => strtoupper($record->billing_document_type ?? '').' '.$record->billing_document_number),
                    Forms\Components\Placeholder::make('requested_name')->label('Nombre o razón social')
                        ->content(fn (Invoice $record) => $record->billing_name),
                    Forms\Components\Placeholder::make('requested_address')->label('Dirección fiscal')
                        ->content(fn (Invoice $record) => $record->billing_address ?: '—'),
                    Forms\Components\Placeholder::make('requested_email')->label('Correo de envío')
                        ->content(fn (Invoice $record) => $record->billing_email ?: '—'),
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
            // Para el comprobante que se emite fuera del sistema (el PDF del
            // contador, un recibo escaneado). El cliente lo descarga desde su
            // portal en lugar del recibo que genera zupraHost.
            Forms\Components\Section::make('Comprobante adjunto')
                ->description('Sube aquí la factura o el recibo en PDF o imagen. El cliente podrá descargarlo.')
                ->collapsed(fn (?Invoice $record) => ! $record?->hasAttachment())
                ->schema([
                    Forms\Components\FileUpload::make('attachment_path')
                        ->label('Archivo')
                        ->disk('public')
                        ->directory('invoices')
                        ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                        ->maxSize(8192)
                        ->downloadable()
                        ->openable()
                        ->helperText('PDF o imagen, hasta 8 MB.'),
                    Forms\Components\TextInput::make('attachment_name')
                        ->label('Nombre a mostrar')
                        ->placeholder('Factura F001-000128'),
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
                Tables\Columns\TextColumn::make('issued_at')->label('Emisión')->date()->sortable()
                    ->description(fn (Invoice $r) => $r->periodLabel()),
                Tables\Columns\TextColumn::make('due_at')->label('Vence')->date()->sortable()
                    ->color(fn ($state, Invoice $r) => $state && $state->isPast() && $r->status !== 'pagado' ? 'danger' : null),
                Tables\Columns\TextColumn::make('total')->label('Total')->money('PEN')->sortable(),
                Tables\Columns\TextColumn::make('paid_sum')->label('Cobrado')->money('PEN')
                    ->state(fn (Invoice $r) => $r->amountPaid())
                    ->color(fn (Invoice $r) => $r->amountPaid() > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('balance')->label('Saldo')->money('PEN')
                    ->state(fn (Invoice $r) => $r->balance())
                    ->weight('bold')
                    ->color(fn (Invoice $r) => $r->balance() > 0 ? 'danger' : 'success'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'pagado', 'warning' => 'pendiente', 'danger' => 'vencido',
                    'gray' => 'anulado', 'info' => 'parcial',
                ]),
                Tables\Columns\BadgeColumn::make('sunat_status')->label('SUNAT')->toggleable()->colors([
                    'gray' => 'no_enviado', 'info' => 'enviado',
                    'success' => 'aceptado', 'danger' => 'rechazado',
                ]),
                Tables\Columns\TextColumn::make('invoice_requested_at')->label('Factura pedida')
                    ->badge()
                    ->state(fn (Invoice $r) => $r->invoiceWasRequested()
                        ? ($r->isElectronicallyIssued() ? 'Emitida' : 'Pendiente')
                        : null)
                    ->color(fn (Invoice $r) => $r->isElectronicallyIssued() ? 'success' : 'warning')
                    ->description(fn (Invoice $r) => $r->invoiceWasRequested()
                        ? $r->billing_document_number
                        : null)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'vencido' => 'Vencido',
                    'anulado' => 'Anulado', 'parcial' => 'Parcial',
                ]),
                Tables\Filters\Filter::make('con_saldo')->label('Solo con saldo pendiente')
                    ->query(fn ($query) => $query->outstanding()),
                Tables\Filters\Filter::make('vencidas')->label('Solo vencidas')
                    ->query(fn ($query) => $query->overdue()),
                Tables\Filters\Filter::make('factura_solicitada')
                    ->label('Con factura solicitada sin emitir')
                    ->query(fn ($query) => $query->whereNotNull('invoice_requested_at')
                        ->where('sunat_status', '!=', 'aceptado')),
            ])
            ->actions([
                // Dar por pagada una factura tiene que dejar rastro en pagos:
                // el portal del cliente suma comprobantes aprobados, no el
                // estado de la factura.
                Tables\Actions\Action::make('registrar_pago')
                    ->label('Registrar cobro')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->modalHeading(fn (Invoice $r) => "Registrar cobro de {$r->code}")
                    ->modalSubmitActionLabel('Registrar')
                    ->visible(fn (Invoice $r) => $r->balance() > 0 && auth()->user()?->can('create payment'))
                    ->fillForm(fn (Invoice $r) => [
                        'amount' => $r->balance(),
                        'method' => 'transferencia',
                        'paid_at' => today(),
                    ])
                    ->form([
                        Forms\Components\TextInput::make('amount')->label('Monto cobrado')
                            ->numeric()->prefix('S/')->required()
                            ->minValue(0.01)
                            ->helperText(fn (Invoice $r) => 'Saldo actual: S/ '.number_format($r->balance(), 2)),
                        Forms\Components\Select::make('method')->label('Método')->options([
                            'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia bancaria',
                            'efectivo' => 'Efectivo', 'manual' => 'Pago manual', 'otro' => 'Otro',
                        ])->required(),
                        Forms\Components\DatePicker::make('paid_at')->label('Fecha del cobro')->required(),
                        Forms\Components\TextInput::make('operation_code')->label('Código de operación'),
                        Forms\Components\Textarea::make('admin_note')->label('Observación'),
                    ])
                    ->action(function (Invoice $r, array $data) {
                        app(PaymentManager::class)->register($r, $data);
                        Notification::make()->title('Cobro registrado')
                            ->body('La factura quedó en estado "'.$r->refresh()->status.'".')
                            ->success()->send();
                    }),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Invoice $r) => $r->status !== 'anulado' && auth()->user()?->can('update invoice'))
                    ->requiresConfirmation()
                    ->action(function (Invoice $r) {
                        $r->update(['status' => 'anulado']);
                        $r->client->syncDebtorStatus();
                    }),
                Tables\Actions\Action::make('enviar_sunat')
                    ->label('Emitir SUNAT')
                    ->icon('heroicon-m-paper-airplane')
                    ->color('info')
                    ->visible(fn (Invoice $r) => in_array($r->document_type, ['boleta', 'factura']) && $r->sunat_status === 'no_enviado' && auth()->user()?->can('update invoice'))
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
