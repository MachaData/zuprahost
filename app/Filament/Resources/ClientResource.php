<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Clientes';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del cliente')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo de cliente')
                        ->options([
                            'natural' => 'Persona natural',
                            'empresa' => 'Empresa',
                            'agencia' => 'Agencia',
                        ])
                        ->default('natural')
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'prospecto' => 'Prospecto',
                            'activo' => 'Activo',
                            'suspendido' => 'Suspendido',
                            'deudor' => 'Deudor',
                        ])
                        ->default('prospecto')
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre o razón social')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\Select::make('document_type')
                        ->label('Tipo de documento')
                        ->options([
                            'dni' => 'DNI',
                            'ruc' => 'RUC',
                            'ce' => 'Carné de extranjería',
                            'pasaporte' => 'Pasaporte',
                            'otro' => 'Otro',
                        ])
                        ->default('dni')
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('document_number')
                        ->label('Número de documento')
                        ->helperText(fn (Forms\Get $get) => $get('document_type') === 'ruc'
                            ? 'Con RUC se puede emitir factura electrónica vía APISPERU.'
                            : null),
                    Forms\Components\Toggle::make('wants_invoice')
                        ->label('¿Desea facturación electrónica?')
                        ->helperText('Si está activo, sus pagos generan comprobante electrónico (factura si tiene RUC, de lo contrario boleta).')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Contacto')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Correo principal')
                        ->email()
                        ->required(),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel(),
                    Forms\Components\TextInput::make('whatsapp')
                        ->label('WhatsApp'),
                    Forms\Components\TextInput::make('address')
                        ->label('Dirección'),
                    Forms\Components\TextInput::make('city')
                        ->label('Ciudad'),
                    Forms\Components\TextInput::make('country')
                        ->label('País')
                        ->default('Perú')
                        ->required(),
                ]),
            Forms\Components\Section::make('Acceso al portal')
                ->description('Vincula un usuario para que el cliente pueda ingresar a clientes.zuprahost.com')
                ->collapsed()
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Usuario vinculado')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload(),
                ]),
            Forms\Components\Textarea::make('notes')
                ->label('Notas internas')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre / Razón social')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Documento')
                    ->formatStateUsing(fn ($state, Client $r) => strtoupper($r->document_type).' '.$state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'gray' => 'natural',
                        'info' => 'empresa',
                        'warning' => 'agencia',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'activo',
                        'gray' => 'prospecto',
                        'warning' => 'suspendido',
                        'danger' => 'deudor',
                    ]),
                Tables\Columns\TextColumn::make('services_count')
                    ->label('Servicios')
                    ->counts('services')
                    ->badge(),
                Tables\Columns\TextColumn::make('registered_at')
                    ->label('Registro')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'prospecto' => 'Prospecto',
                        'suspendido' => 'Suspendido',
                        'deudor' => 'Deudor',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'natural' => 'Persona natural',
                        'empresa' => 'Empresa',
                        'agencia' => 'Agencia',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\DomainsRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
