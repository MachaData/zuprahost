<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'ticket';
    }

    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Soporte';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'tickets';

    public static function categories(): array
    {
        return [
            'hosting' => 'Hosting', 'dominio' => 'Dominio', 'correo' => 'Correo corporativo',
            'facturacion' => 'Facturación', 'wordpress' => 'WordPress', 'seo' => 'SEO',
            'licencias' => 'Licencias', 'soporte' => 'Soporte técnico', 'otro' => 'Otro',
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['abierto', 'en_proceso'])->count();

        return $count ? (string) $count : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\TextInput::make('code')->label('Código')
                    ->default(fn () => Ticket::generateCode())->required(),
                Forms\Components\TextInput::make('subject')->label('Asunto')->required()->columnSpanFull(),
                Forms\Components\Select::make('category')->label('Categoría')->options(self::categories())->default('soporte')->required(),
                Forms\Components\Select::make('priority')->label('Prioridad')->options([
                    'baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'urgente' => 'Urgente',
                ])->default('media')->required(),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'abierto' => 'Abierto', 'en_proceso' => 'En proceso',
                    'esperando_cliente' => 'Esperando respuesta del cliente',
                    'resuelto' => 'Resuelto', 'cerrado' => 'Cerrado',
                ])->default('abierto')->required(),
                Forms\Components\Select::make('assigned_to')->label('Asignado a')
                    ->relationship('assignee', 'name')->searchable()->preload(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subject')->label('Asunto')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable(),
                Tables\Columns\TextColumn::make('category')->label('Categoría')->badge()
                    ->formatStateUsing(fn ($state) => self::categories()[$state] ?? $state),
                Tables\Columns\BadgeColumn::make('priority')->label('Prioridad')->colors([
                    'gray' => 'baja', 'info' => 'media', 'warning' => 'alta', 'danger' => 'urgente',
                ]),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'info' => 'abierto', 'warning' => ['en_proceso', 'esperando_cliente'],
                    'success' => 'resuelto', 'gray' => 'cerrado',
                ]),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'abierto' => 'Abierto', 'en_proceso' => 'En proceso',
                    'esperando_cliente' => 'Esperando cliente', 'resuelto' => 'Resuelto', 'cerrado' => 'Cerrado',
                ]),
                Tables\Filters\SelectFilter::make('priority')->label('Prioridad')->options([
                    'baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'urgente' => 'Urgente',
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RepliesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
