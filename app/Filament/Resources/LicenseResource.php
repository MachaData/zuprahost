<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseResource\Pages;
use App\Models\License;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'license';
    }

    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Servicios';

    protected static ?string $modelLabel = 'licencia';

    protected static ?string $pluralModelLabel = 'licencias';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('Producto')
                    ->relationship('product', 'name')->searchable()->preload(),
                Forms\Components\TextInput::make('license_key')->label('Clave de licencia')
                    ->default(fn () => License::generateKey())->required()->unique(ignoreRecord: true)->columnSpanFull(),
                Forms\Components\TextInput::make('authorized_domain')->label('Dominio autorizado'),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'activa' => 'Activa', 'vencida' => 'Vencida',
                    'suspendida' => 'Suspendida', 'cancelada' => 'Cancelada',
                ])->default('activa')->required(),
                Forms\Components\DatePicker::make('activated_at')->label('Fecha de activación')->default(now()),
                Forms\Components\DatePicker::make('expires_at')->label('Fecha de vencimiento'),
                Forms\Components\TextInput::make('max_activations')->label('Activaciones permitidas')->numeric()->default(1),
                Forms\Components\TextInput::make('used_activations')->label('Activaciones usadas')->numeric()->default(0),
                Forms\Components\TextInput::make('version')->label('Versión'),
                Forms\Components\TextInput::make('download_url')->label('URL de descarga')->url(),
                Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('license_key')->label('Clave')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('authorized_domain')->label('Dominio'),
                Tables\Columns\TextColumn::make('expires_at')->label('Vence')->date()->sortable(),
                Tables\Columns\TextColumn::make('used_activations')->label('Usos')
                    ->formatStateUsing(fn ($state, License $r) => "{$state}/{$r->max_activations}"),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activa', 'danger' => 'vencida',
                    'warning' => 'suspendida', 'gray' => 'cancelada',
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'activa' => 'Activa', 'vencida' => 'Vencida',
                    'suspendida' => 'Suspendida', 'cancelada' => 'Cancelada',
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
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
