<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainResource\Pages;
use App\Models\Domain;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DomainResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'domain';
    }

    protected static ?string $model = Domain::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Servicios';

    protected static ?string $modelLabel = 'dominio';

    protected static ?string $pluralModelLabel = 'dominios';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('name')->label('Dominio')->required()
                    ->placeholder('ejemplo.com'),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'activo' => 'Activo', 'por_vencer' => 'Por vencer',
                    'vencido' => 'Vencido', 'suspendido' => 'Suspendido',
                ])->default('activo')->required(),
                Forms\Components\DatePicker::make('registered_at')->label('Fecha de registro'),
                Forms\Components\DatePicker::make('expires_at')->label('Fecha de vencimiento'),
                Forms\Components\TextInput::make('renewal_price')->label('Precio de renovación')->numeric()->prefix('S/')->default(0),
                Forms\Components\TextInput::make('cost')->label('Costo interno')->numeric()->prefix('S/')->default(0),
                Forms\Components\Toggle::make('whois_protection')->label('Protección WHOIS'),
                Forms\Components\Toggle::make('auto_renew')->label('Renovación automática'),
                Forms\Components\Textarea::make('nameservers')->label('Nameservers')->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
            ]),

            // Quién registró el dominio y quién lo renueva son dos decisiones
            // distintas: uno traído de fuera puede pasar a renovarse con
            // nosotros sin dejar de ser externo.
            Forms\Components\Section::make('Origen y renovación')
                ->description('Define si el dominio lo vendimos nosotros y quién se encarga de renovarlo.')
                ->columns(2)
                ->schema([
                    Forms\Components\Radio::make('origin')->label('¿De dónde viene?')
                        ->options([
                            'propio' => 'Lo registramos nosotros',
                            'externo' => 'El cliente lo trajo de otro proveedor',
                        ])
                        ->descriptions([
                            'propio' => 'Está en nuestra cuenta de registrador.',
                            'externo' => 'Está en GoDaddy, Namecheap, otro… a nombre del cliente.',
                        ])
                        ->default('propio')
                        ->live()
                        ->required(),

                    Forms\Components\Group::make()->schema([
                        Forms\Components\Toggle::make('renewal_managed')
                            ->label('La renovación corre por nuestra cuenta')
                            ->helperText('Si se apaga, el dominio no se cobra ni entra en el centro de renovaciones del cliente.')
                            ->default(true)
                            ->live(),

                        Forms\Components\TextInput::make('provider')
                            ->label(fn (Forms\Get $get) => $get('origin') === 'externo' ? 'Proveedor actual' : 'Registrador')
                            ->placeholder(fn (Forms\Get $get) => $get('origin') === 'externo' ? 'GoDaddy, Namecheap…' : config('app.name'))
                            ->default(fn () => config('app.name')),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Dominio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('origin')->label('Origen')->badge()
                    ->formatStateUsing(fn (Domain $r) => $r->isExternal() ? 'Externo' : 'Propio')
                    ->color(fn (Domain $r) => $r->isExternal() ? 'info' : 'success')
                    ->description(fn (Domain $r) => $r->renewal_managed ? 'Renovamos nosotros' : 'Renueva el cliente'),
                Tables\Columns\TextColumn::make('provider')->label('Proveedor')->toggleable(),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date()->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->diffInDays(now()) <= 30 ? 'warning' : null)),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activo', 'warning' => 'por_vencer',
                    'danger' => 'vencido', 'gray' => 'suspendido',
                ]),
                Tables\Columns\IconColumn::make('auto_renew')->label('Auto')->boolean()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'activo' => 'Activo', 'por_vencer' => 'Por vencer',
                    'vencido' => 'Vencido', 'suspendido' => 'Suspendido',
                ]),
                Tables\Filters\SelectFilter::make('origin')->label('Origen')->options([
                    'propio' => 'Registrados con nosotros',
                    'externo' => 'Traídos por el cliente',
                ]),
                Tables\Filters\TernaryFilter::make('renewal_managed')->label('Renovación')
                    ->placeholder('Todos')
                    ->trueLabel('La gestionamos nosotros')
                    ->falseLabel('La gestiona el cliente'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('expires_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDomains::route('/'),
            'create' => Pages\CreateDomain::route('/create'),
            'edit' => Pages\EditDomain::route('/{record}/edit'),
        ];
    }
}
