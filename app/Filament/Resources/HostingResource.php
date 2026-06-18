<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HostingResource\Pages;
use App\Models\Hosting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HostingResource extends Resource
{
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
                    ->relationship('service', 'name')->searchable()->preload(),
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
