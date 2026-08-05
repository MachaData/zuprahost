<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'payment';
    }

    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $modelLabel = 'método de pago';

    protected static ?string $pluralModelLabel = 'métodos de pago';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nombre')->required()
                    ->placeholder('Yape, Transferencia BCP, Izipay…'),
                Forms\Components\TextInput::make('code')->label('Código interno')->required()
                    ->unique(ignoreRecord: true)
                    ->alphaDash()
                    ->helperText('Se guarda en cada pago. No lo cambies una vez usado.'),
                Forms\Components\Select::make('kind')->label('Cómo paga el cliente')
                    ->options([
                        'manual' => 'Transfiere y sube su comprobante',
                        'enlace' => 'Paga en una pasarela externa',
                    ])
                    ->default('manual')
                    ->live()
                    ->required(),
                Forms\Components\Toggle::make('requires_receipt')
                    ->label('Exigir comprobante')
                    ->default(true)
                    ->helperText('Apágalo si la pasarela confirma el pago por su cuenta.'),
                Forms\Components\Toggle::make('is_active')->label('Activo')->default(true),
                Forms\Components\TextInput::make('sort')->label('Orden')->numeric()->default(0),
            ]),

            Forms\Components\Section::make('Datos de la cuenta')
                ->description('Lo que el cliente necesita para pagarte. Deja vacío lo que no aplique.')
                ->columns(2)
                ->visible(fn (Forms\Get $get) => $get('kind') === 'manual')
                ->schema([
                    Forms\Components\TextInput::make('bank')->label('Banco')->placeholder('BCP, Interbank…'),
                    Forms\Components\TextInput::make('account_holder')->label('Titular'),
                    Forms\Components\TextInput::make('account_number')->label('Número de cuenta'),
                    Forms\Components\TextInput::make('cci')->label('CCI'),
                    Forms\Components\TextInput::make('phone')->label('Número de celular')
                        ->helperText('Para Yape o Plin.'),
                    Forms\Components\FileUpload::make('qr_path')->label('Código QR')
                        ->image()->disk('public')->directory('payment-methods')
                        ->helperText('Opcional: el cliente lo escanea desde su portal.'),
                ]),

            Forms\Components\Section::make('Pasarela')
                ->visible(fn (Forms\Get $get) => $get('kind') === 'enlace')
                ->schema([
                    Forms\Components\TextInput::make('url')->label('Enlace de pago general')
                        ->url()
                        ->helperText('Déjalo vacío si el enlace se carga por servicio.'),
                ]),

            Forms\Components\Textarea::make('instructions')
                ->label('Instrucciones para el cliente')
                ->placeholder('Transfiere el monto exacto y sube la constancia. Validamos en menos de 24 horas.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('qr_path')->label('QR')->circular()->toggleable(),
                Tables\Columns\TextColumn::make('name')->label('Método')->searchable()->sortable()
                    ->description(fn (PaymentMethod $r) => $r->bank ?: $r->account_number),
                Tables\Columns\TextColumn::make('kind')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'enlace' ? 'Pasarela' : 'Manual')
                    ->color(fn (string $state) => $state === 'enlace' ? 'info' : 'gray'),
                Tables\Columns\IconColumn::make('requires_receipt')->label('Comprobante')->boolean(),
                Tables\Columns\TextColumn::make('clients_count')->label('Clientes')
                    ->counts('clients')->badge()
                    ->tooltip('Clientes con este método asignado a mano. Vacío = disponible para todos.'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Activo'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Estado')
                    ->placeholder('Todos')->trueLabel('Activos')->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('sort')
            ->reorderable('sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
