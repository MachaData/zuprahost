<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Models\User;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'client';
    }

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
                ->description('Sin usuario vinculado el cliente no puede entrar. Usa «Dar acceso al portal» en el listado para crearlo de una vez.')
                ->collapsed(fn (?Client $record) => $record?->user_id !== null)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Usuario vinculado')
                        // Solo usuarios con rol Cliente y que no pertenezcan ya
                        // a otro cliente: vincular un admin aquí le daría a esa
                        // cuenta el portal de otra persona.
                        ->relationship(
                            'user',
                            'email',
                            fn ($query, ?Client $record) => $query
                                ->whereHas('roles', fn ($q) => $q->where('name', 'Cliente'))
                                ->where(fn ($q) => $q
                                    ->doesntHave('client')
                                    ->when($record?->user_id, fn ($q, $id) => $q->orWhere('users.id', $id))),
                        )
                        ->searchable()
                        ->preload()
                        ->placeholder('Sin acceso al portal'),
                ]),
            Forms\Components\Section::make('Métodos de pago')
                ->description('Sin marcar nada, el cliente ve todos los métodos activos. Marca solo si quieres limitarlo a algunos.')
                ->collapsed(fn (?Client $record) => ! $record?->hasCustomPaymentMethods())
                ->schema([
                    Forms\Components\CheckboxList::make('paymentMethods')
                        ->label('Limitar a estos métodos')
                        ->relationship('paymentMethods', 'name')
                        ->descriptions(fn () => \App\Models\PaymentMethod::pluck('bank', 'id')->filter()->all())
                        ->columns(3)
                        ->bulkToggleable()
                        ->helperText('El enlace de pago no se marca aquí: se carga en cada servicio.'),
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
                Tables\Columns\IconColumn::make('user_id')
                    ->label('Portal')
                    ->boolean()
                    ->tooltip(fn (Client $r) => $r->user_id ? 'Puede entrar al portal' : 'Sin acceso al portal'),
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
                // Crear el usuario y vincularlo en un solo paso: hacerlo en dos
                // pantallas deja clientes sin acceso, que es un 403 al entrar.
                Tables\Actions\Action::make('crear_acceso')
                    ->label('Dar acceso al portal')
                    ->icon('heroicon-m-key')
                    ->color('success')
                    ->visible(fn (Client $r) => $r->user_id === null)
                    ->modalHeading(fn (Client $r) => "Dar acceso a {$r->name}")
                    ->modalSubmitActionLabel('Crear acceso')
                    ->fillForm(fn (Client $r) => ['email' => $r->email, 'name' => $r->name])
                    ->form([
                        Forms\Components\TextInput::make('name')->label('Nombre del usuario')->required(),
                        Forms\Components\TextInput::make('email')->label('Correo de acceso')
                            ->email()->required()
                            ->unique(table: User::class, column: 'email'),
                        Forms\Components\TextInput::make('password')->label('Contraseña temporal')
                            ->password()->revealable()->required()
                            ->rule(\Illuminate\Validation\Rules\Password::defaults())
                            ->default(fn () => \App\Support\Credentials::password())
                            ->helperText('Cópiala antes de guardar: no se vuelve a mostrar. El cliente puede cambiarla desde «Mi cuenta».'),
                    ])
                    ->action(function (Client $record, array $data) {
                        $user = User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'password' => Hash::make($data['password']),
                            'password_changed_at' => now(),
                        ]);
                        $user->assignRole('Cliente');
                        $record->update(['user_id' => $user->id]);

                        Notification::make()
                            ->title('Acceso creado')
                            ->body("{$data['email']} ya puede entrar en ".url('/client/login'))
                            ->success()->persistent()->send();
                    }),

                // Un cliente que olvida su contraseña llama por teléfono. Sin
                // esto había que entrar por consola a cambiarla a mano.
                Tables\Actions\Action::make('restablecer_acceso')
                    ->label('Restablecer contraseña')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->visible(fn (Client $r) => $r->user_id !== null)
                    ->modalHeading(fn (Client $r) => "Nueva contraseña para {$r->name}")
                    ->modalDescription('Se cierran además todas sus sesiones abiertas.')
                    ->modalSubmitActionLabel('Restablecer')
                    ->fillForm(fn () => ['password' => \App\Support\Credentials::password()])
                    ->form([
                        Forms\Components\TextInput::make('password')->label('Contraseña temporal')
                            ->password()->revealable()->required()
                            ->rule(\Illuminate\Validation\Rules\Password::defaults())
                            ->helperText('Cópiala antes de guardar: no se vuelve a mostrar.'),
                    ])
                    ->action(function (Client $record, array $data) {
                        $user = $record->user;

                        if (! $user) {
                            return;
                        }

                        $user->forceFill([
                            'password' => Hash::make($data['password']),
                            'password_changed_at' => now(),
                            // Invalida las cookies de «recordarme».
                            'remember_token' => Str::random(60),
                        ])->save();

                        if (config('session.driver') === 'database') {
                            \Illuminate\Support\Facades\DB::table('sessions')
                                ->where('user_id', $user->getKey())
                                ->delete();
                        }

                        Notification::make()
                            ->title('Contraseña restablecida')
                            ->body("Pásasela a {$user->email} por un canal seguro. Debería cambiarla al entrar.")
                            ->success()->persistent()->send();
                    }),

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
