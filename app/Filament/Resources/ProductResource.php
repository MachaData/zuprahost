<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
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
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
                Tables\Columns\IconColumn::make('is_renewable')->label('Renovable')->boolean()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Categoría')->options(self::categories()),
                Tables\Filters\TernaryFilter::make('is_active')->label('Activo'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
