<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Filament\Concerns\AuthorizesWithPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductResource extends Resource
{
    use AuthorizesWithPermissions;

    public static function permissionName(): string
    {
        return 'product';
    }

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos y planes';

    public static function categories(): array
    {
        return [
            'hosting' => 'Hosting',
            'dominio' => 'Dominio',
            'vps' => 'VPS',
            'correo' => 'Correo corporativo',
            'ssl' => 'Certificado SSL',
            'mantenimiento' => 'Mantenimiento WordPress',
            'seo' => 'SEO mensual',
            'optimizacion' => 'Optimización web',
            'licencia' => 'Licencia de plugin',
            'desarrollo' => 'Desarrollo web',
            'soporte' => 'Soporte técnico',
            'migracion' => 'Migración web',
            'backup' => 'Backups',
            'landing' => 'Landing page',
            'anuncios' => 'Gestión de anuncios',
            'consultoria' => 'Consultoría',
            'otro' => 'Otro',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nombre del producto')->required()->columnSpanFull(),
                Forms\Components\Select::make('category')->label('Categoría')->options(self::categories())->required(),
                Forms\Components\Select::make('billing_cycle')->label('Tipo de cobro')->options([
                    'mensual' => 'Mensual', 'anual' => 'Anual', 'unico' => 'Pago único',
                ])->default('mensual')->required(),
                Forms\Components\TextInput::make('price')->label('Precio de venta')->numeric()->prefix('S/')->default(0)->required(),
                Forms\Components\TextInput::make('cost')->label('Costo interno')->numeric()->prefix('S/')->default(0),
                Forms\Components\Toggle::make('is_active')->label('Activo')->default(true),
                Forms\Components\Toggle::make('is_renewable')->label('Renovable')->default(true),
                Forms\Components\Textarea::make('description')->label('Descripción')->columnSpanFull(),
            ]),

            // Lo que distingue un plan de otro. Se copia como límite al dar de
            // alta el hosting del cliente y se muestra en el detalle del
            // servicio como "Lo que incluye tu plan".
            Forms\Components\Section::make('Qué incluye el plan')
                ->description('Deja en blanco lo que no aplique a este producto. 0 significa ilimitado.')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('email_accounts_limit')->label('Cuentas de correo')
                        ->numeric()->placeholder('No aplica'),
                    Forms\Components\TextInput::make('disk_limit_mb')->label('Espacio en disco')
                        ->numeric()->suffix('MB')->placeholder('No aplica'),
                    Forms\Components\TextInput::make('bandwidth_limit_mb')->label('Transferencia mensual')
                        ->numeric()->suffix('MB')->placeholder('No aplica'),
                    Forms\Components\TextInput::make('websites_limit')->label('Sitios web')
                        ->numeric()->placeholder('No aplica'),
                    Forms\Components\TextInput::make('databases_limit')->label('Bases de datos')
                        ->numeric()->placeholder('No aplica'),
                    Forms\Components\Select::make('panel_type')->label('Panel que se entrega')
                        ->options(\App\Models\ServiceAccess::TYPES)
                        ->placeholder('Ninguno')
                        ->helperText('Prellena el acceso al dar de alta el servicio.'),

                    Forms\Components\Repeater::make('specs')
                        ->label('Otras características')
                        ->helperText('Lo que no es un número: "Backups diarios", "SSL incluido", "8 GB de RAM"…')
                        ->columns(2)
                        ->columnSpanFull()
                        ->defaultItems(0)
                        ->addActionLabel('Añadir característica')
                        ->schema([
                            Forms\Components\TextInput::make('label')->label('Característica')->required(),
                            Forms\Components\TextInput::make('value')->label('Detalle')->placeholder('✓'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Producto')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Categoría')->badge()
                    ->formatStateUsing(fn ($state) => self::categories()[$state] ?? $state),
                Tables\Columns\TextColumn::make('billing_cycle')->label('Cobro')->badge(),
                Tables\Columns\TextColumn::make('price')->label('Precio')->money('PEN')->sortable(),
                Tables\Columns\TextColumn::make('cost')->label('Costo')->money('PEN')->toggleable(),
                // Cuántos clientes dependen de este plan: es lo que decide si
                // un cambio de precio o una baja son inocuos o no.
                Tables\Columns\TextColumn::make('services_count')->label('Contratado por')
                    ->counts('services')
                    ->suffix(' servicios')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\IconColumn::make('is_renewable')->label('Renovable')->boolean()->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Categoría')->options(self::categories()),
                Tables\Filters\TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Casi todos los planes nacen como variante de otro: copiar y
                // ajustar evita volver a escribir quince características.
                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    // services_count no es una columna: lo añade el withCount
                    // de la tabla y al copiarlo el insert falla.
                    ->excludeAttributes(['created_at', 'updated_at', 'services_count'])
                    ->beforeReplicaSaved(function (Product $replica): void {
                        $replica->name = $replica->name.' (copia)';
                        // Sale desactivado a propósito: un plan a medio ajustar
                        // no debe poder contratarse.
                        $replica->is_active = false;
                    })
                    ->successRedirectUrl(fn (Product $replica): string => static::getUrl('edit', ['record' => $replica])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('Los servicios ya contratados no cambian; el plan solo deja de ofrecerse.')
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),

                    // Subir la lista de precios un 8 % a mano, plan por plan,
                    // es donde se cuelan los errores de tipeo.
                    Tables\Actions\BulkAction::make('adjustPrice')
                        ->label('Ajustar precios')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color('primary')
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Forms\Components\Radio::make('mode')->label('Cómo ajustar')
                                ->options([
                                    'percent' => 'Por porcentaje',
                                    'amount' => 'Sumando o restando un monto',
                                ])
                                ->default('percent')
                                ->required(),
                            Forms\Components\TextInput::make('value')->label('Valor')
                                ->numeric()
                                ->required()
                                ->helperText('Usa negativos para bajar precios: -10 es un 10 % menos o S/ 10 menos.'),
                            Forms\Components\Toggle::make('round')->label('Redondear a soles enteros')->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $product) {
                                $price = $data['mode'] === 'percent'
                                    ? $product->price * (1 + ((float) $data['value'] / 100))
                                    : $product->price + (float) $data['value'];

                                // Nunca por debajo de cero: un precio negativo
                                // generaría facturas en negativo.
                                $price = max(0, $data['round'] ? round($price) : round($price, 2));

                                $product->update(['price' => $price]);
                            }

                            Notification::make()
                                ->title($records->count().' precios actualizados')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
