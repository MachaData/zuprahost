<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DomainResource\Pages;
use App\Models\Domain;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DomainResource extends Resource
{
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
                Forms\Components\TextInput::make('provider')->label('Proveedor del dominio'),
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Dominio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
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
