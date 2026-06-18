<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Services\ServiceManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'Servicios';

    protected static ?string $modelLabel = 'servicio';

    protected static ?string $pluralModelLabel = 'servicios contratados';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('client_id')->label('Cliente')
                    ->relationship('client', 'name')->searchable()->preload()->required(),
                Forms\Components\Select::make('product_id')->label('Producto o plan')
                    ->relationship('product', 'name')->searchable()->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($product = \App\Models\Product::find($state)) {
                            $set('name', $product->name);
                            $set('price', $product->price);
                            $set('billing_cycle', $product->billing_cycle);
                        }
                    }),
                Forms\Components\TextInput::make('name')->label('Nombre personalizado')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('price')->label('Precio')->numeric()->prefix('S/')->default(0)->required(),
                Forms\Components\Select::make('billing_cycle')->label('Ciclo de pago')->options([
                    'mensual' => 'Mensual', 'anual' => 'Anual', 'unico' => 'Pago único',
                ])->default('mensual')->required(),
                Forms\Components\Select::make('status')->label('Estado')->options([
                    'pendiente' => 'Pendiente', 'activo' => 'Activo', 'suspendido' => 'Suspendido',
                    'cancelado' => 'Cancelado', 'vencido' => 'Vencido',
                ])->default('pendiente')->required(),
                Forms\Components\Select::make('domain_id')->label('Dominio relacionado')
                    ->relationship('domain', 'name')->searchable()->preload(),
                Forms\Components\DatePicker::make('starts_at')->label('Fecha de inicio')->default(now()),
                Forms\Components\DatePicker::make('ends_at')->label('Fecha de vencimiento'),
                Forms\Components\DatePicker::make('next_renewal_at')->label('Próxima renovación'),
                Forms\Components\Toggle::make('auto_renew')->label('Renovación automática'),
                Forms\Components\Textarea::make('notes')->label('Notas internas')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Servicio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('client.name')->label('Cliente')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price')->label('Precio')->money('PEN'),
                Tables\Columns\TextColumn::make('billing_cycle')->label('Ciclo')->badge(),
                Tables\Columns\TextColumn::make('ends_at')->label('Vence')->date()->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')->colors([
                    'success' => 'activo', 'gray' => ['pendiente', 'cancelado'],
                    'warning' => 'suspendido', 'danger' => 'vencido',
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options([
                    'activo' => 'Activo', 'pendiente' => 'Pendiente', 'suspendido' => 'Suspendido',
                    'cancelado' => 'Cancelado', 'vencido' => 'Vencido',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('renovar')
                    ->label('Renovar')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('new_ends_at')
                            ->label('Nuevo vencimiento')
                            ->default(fn (Service $record) => app(ServiceManager::class)->suggestNextEnd($record))
                            ->required(),
                        Forms\Components\TextInput::make('amount')->label('Monto')->numeric()->prefix('S/')
                            ->default(fn (Service $record) => $record->price),
                        Forms\Components\Toggle::make('generate_invoice')->label('Generar factura')->default(true),
                    ])
                    ->action(function (Service $record, array $data) {
                        app(ServiceManager::class)->renew(
                            $record,
                            \Illuminate\Support\Carbon::parse($data['new_ends_at']),
                            (float) ($data['amount'] ?? $record->price),
                            (bool) ($data['generate_invoice'] ?? false),
                        );
                        \Filament\Notifications\Notification::make()
                            ->title('Servicio renovado')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ends_at', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RenewalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
