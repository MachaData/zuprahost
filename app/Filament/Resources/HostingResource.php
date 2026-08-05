<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostingResource\Pages;
use App\Models\Hosting;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HostingResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'hosting';
    }

    protected static ?string $model = Hosting::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationGroup = 'Servicios';

    protected static ?string $modelLabel = 'hosting';

    protected static ?string $pluralModelLabel = 'hosting';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Cuenta')->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('domain_id')->label('Dominio principal')
                    ->relationship('domain', 'name')->searchable()->preload(),
                Forms\Components\Select::make('service_id')->label('Servicio asociado')
                    ->relationship('service', 'name')->searchable()->preload()
                    ->live()
                    // Los límites del plan son los del producto contratado:
                    // se copian para no volver a teclearlos en cada alta.
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $product = \App\Models\Service::with('product')->find($state)?->product;

                        if (! $product) {
                            return;
                        }

                        $set('plan', $product->name);
                        $set('disk_limit_mb', $product->disk_limit_mb);
                        $set('bandwidth_limit_mb', $product->bandwidth_limit_mb);
                        $set('websites_limit', $product->websites_limit);
                        $set('email_accounts_limit', $product->email_accounts_limit);
                        $set('databases_limit_count', $product->databases_limit);
                    }),
                Forms\Components\TextInput::make('plan')->label('Plan de hosting'),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'activo' => 'Activo', 'suspendido' => 'Suspendido',
                    'vencido' => 'Vencido', 'cancelado' => 'Cancelado',
                ])->default('activo')->required(),
                Forms\Components\DatePicker::make('expires_at')->label('Vencimiento'),
            ]),
            Forms\Components\Section::make('Datos técnicos (DirectAdmin)')->columns(2)->schema([
                Forms\Components\TextInput::make('server')->label('Servidor'),
                Forms\Components\TextInput::make('server_ip')->label('IP del servidor'),
                Forms\Components\TextInput::make('directadmin_user')->label('Usuario DirectAdmin'),
                Forms\Components\TextInput::make('disk_space')->label('Espacio asignado'),
                Forms\Components\TextInput::make('bandwidth')->label('Ancho de banda'),
                Forms\Components\TextInput::make('email_accounts')->label('Correos permitidos'),
                Forms\Components\TextInput::make('databases_limit')->label('Bases de datos permitidas'),
                Forms\Components\TextInput::make('datacenter')->label('Centro de datos')
                    ->placeholder('Lima, Perú'),
            ]),
            // Lo de arriba es texto libre para describir el plan; esto es
            // numérico porque alimenta las barras de consumo del cliente.
            Forms\Components\Section::make('Consumo de recursos')
                ->description('Lo que ve el cliente como uso de su plan. Déjalo vacío si todavía no mides.')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('disk_used_mb')->label('Disco usado')->numeric()->suffix('MB'),
                    Forms\Components\TextInput::make('disk_limit_mb')->label('Disco del plan')->numeric()->suffix('MB'),
                    Forms\Components\Placeholder::make('disk_percent')->label('Uso de disco')
                        ->content(fn (?Hosting $record) => $record?->diskUsagePercent() !== null
                            ? $record->diskUsagePercent().'%'
                            : 'Sin medición'),

                    Forms\Components\TextInput::make('bandwidth_used_mb')->label('Transferencia usada')->numeric()->suffix('MB'),
                    Forms\Components\TextInput::make('bandwidth_limit_mb')->label('Transferencia del plan')->numeric()->suffix('MB'),
                    Forms\Components\Placeholder::make('bandwidth_percent')->label('Uso de transferencia')
                        ->content(fn (?Hosting $record) => $record?->bandwidthUsagePercent() !== null
                            ? $record->bandwidthUsagePercent().'%'
                            : 'Sin medición'),

                    Forms\Components\TextInput::make('email_accounts_used')->label('Cuentas de correo creadas')->numeric(),
                    Forms\Components\TextInput::make('email_accounts_limit')->label('Cuentas del plan')->numeric(),
                    Forms\Components\Placeholder::make('email_percent')->label('Uso de cuentas')
                        ->content(fn (?Hosting $record) => $record?->emailUsagePercent() !== null
                            ? $record->emailUsagePercent().'%'
                            : 'Sin medición'),

                    Forms\Components\TextInput::make('websites_used')->label('Sitios publicados')->numeric(),
                    Forms\Components\TextInput::make('websites_limit')->label('Sitios del plan')->numeric(),
                    Forms\Components\DateTimePicker::make('usage_updated_at')->label('Medido el'),

                    Forms\Components\TextInput::make('databases_used')->label('Bases de datos creadas')->numeric(),
                    Forms\Components\TextInput::make('databases_limit_count')->label('Bases de datos del plan')->numeric(),
                ]),
            Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain.name')->label('Dominio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('plan')->label('Plan'),
                Tables\Columns\TextColumn::make('server')->label('Servidor')->toggleable(),
                Tables\Columns\TextColumn::make('disk_used_mb')->label('Disco')
                    ->state(fn (Hosting $r) => $r->diskUsagePercent() !== null ? $r->diskUsagePercent().'%' : '—')
                    ->badge()
                    ->color(fn (Hosting $r) => match (true) {
                        $r->diskUsagePercent() === null => 'gray',
                        $r->diskUsagePercent() >= Hosting::CRITICAL_USAGE => 'danger',
                        $r->diskUsagePercent() >= 75 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activo', 'warning' => 'suspendido',
                    'danger' => 'vencido', 'gray' => 'cancelado',
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'activo' => 'Activo', 'suspendido' => 'Suspendido',
                    'vencido' => 'Vencido', 'cancelado' => 'Cancelado',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostings::route('/'),
            'create' => Pages\CreateHosting::route('/create'),
            'edit' => Pages\EditHosting::route('/{record}/edit'),
        ];
    }
}
