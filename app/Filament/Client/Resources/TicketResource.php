<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Concerns\ScopedToClient;
use App\Filament\Client\Resources\TicketResource\Pages;
use App\Filament\Client\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    use ScopedToClient;

    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Soporte';

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'soporte';

    protected static ?int $navigationSort = 4;

    public static function categories(): array
    {
        return [
            'hosting' => 'Hosting', 'dominio' => 'Dominio', 'correo' => 'Correo corporativo',
            'facturacion' => 'Facturación', 'wordpress' => 'WordPress', 'seo' => 'SEO',
            'licencias' => 'Licencias', 'soporte' => 'Soporte técnico', 'otro' => 'Otro',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('subject')->label('Asunto')->required()->columnSpanFull(),
            Forms\Components\Select::make('category')->label('Categoría')->options(self::categories())->default('soporte')->required(),
            Forms\Components\Select::make('priority')->label('Prioridad')->options([
                'baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'urgente' => 'Urgente',
            ])->default('media')->required(),
            Forms\Components\Textarea::make('initial_message')
                ->label('Describe tu solicitud')
                ->required()
                ->rows(5)
                ->columnSpanFull()
                ->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable(),
                Tables\Columns\TextColumn::make('subject')->label('Asunto')->limit(40),
                Tables\Columns\TextColumn::make('category')->label('Categoría')->badge()
                    ->formatStateUsing(fn ($state) => self::categories()[$state] ?? $state),
                Tables\Columns\BadgeColumn::make('priority')->label('Prioridad')->colors([
                    'gray' => 'baja', 'info' => 'media', 'warning' => 'alta', 'danger' => 'urgente',
                ]),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'info' => 'abierto', 'warning' => ['en_proceso', 'esperando_cliente'],
                    'success' => 'resuelto', 'gray' => 'cerrado',
                ]),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->since(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}
