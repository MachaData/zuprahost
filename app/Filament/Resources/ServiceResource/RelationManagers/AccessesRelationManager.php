<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use App\Models\ServiceAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Accesos que el cliente ve en su portal: panel de hosting, webmail, consola
 * del VPS, portal de licencias… Aquí no se guardan contraseñas.
 */
class AccessesRelationManager extends RelationManager
{
    protected static string $relationship = 'accesses';

    protected static ?string $title = 'Accesos del cliente';

    protected static ?string $modelLabel = 'acceso';

    protected static ?string $pluralModelLabel = 'accesos';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->label('Tipo de acceso')
                ->options(ServiceAccess::TYPES)
                ->default('directadmin')
                ->live()
                ->required(),
            Forms\Components\TextInput::make('label')->label('Nombre a mostrar')
                ->placeholder(fn (Forms\Get $get) => ServiceAccess::TYPES[$get('type')] ?? 'Acceso')
                ->helperText('Opcional: si lo dejas vacío se usa el nombre del tipo.'),
            Forms\Components\TextInput::make('url')->label('Enlace de acceso')
                ->url()
                ->placeholder('https://srv-lima-01.zuprahost.com:2222')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('username')->label('Usuario')
                ->helperText('La contraseña se entrega por soporte; no se guarda aquí.'),
            Forms\Components\TextInput::make('sort')->label('Orden')->numeric()->default(0),
            Forms\Components\Textarea::make('instructions')->label('Instrucciones para el cliente')
                ->placeholder('Entra con tu usuario y la contraseña que te enviamos por correo.')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_visible')->label('Visible en el portal del cliente')
                ->default(true)
                ->helperText('Apágalo para accesos internos que el cliente no debe ver.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state) => ServiceAccess::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('label')->label('Nombre')
                    ->state(fn (ServiceAccess $r) => $r->displayLabel()),
                Tables\Columns\TextColumn::make('url')->label('Enlace')
                    ->url(fn (ServiceAccess $r) => $r->url, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->limit(40)
                    ->placeholder('Sin enlace'),
                Tables\Columns\TextColumn::make('username')->label('Usuario')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_visible')->label('Visible')->boolean(),
            ])
            ->defaultSort('sort')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Añadir acceso'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin accesos cargados')
            ->emptyStateDescription('Añade el panel, el webmail o la consola para que el cliente entre desde su portal.');
    }
}
