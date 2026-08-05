<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Resources\InvoiceResource;
use App\Models\Client;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Cobranzas de un vistazo: cuánto se facturó, cuánto entró de verdad y
 * cuánto falta por cliente. "Cobrado" cuenta solo comprobantes validados.
 */
class AccountStatement extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Estado de cuenta';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Estado de cuenta';

    protected static string $view = 'filament.pages.account-statement';

    /**
     * Totales de la cartera completa, no solo de la página visible ni del
     * filtro activo: son el encabezado de la cobranza.
     */
    public function getTotals(): array
    {
        $totals = Client::query()->withAccountTotals()->get();

        return [
            'invoiced' => (float) $totals->sum('invoiced_sum'),
            'collected' => (float) $totals->sum('collected_sum'),
            'balance' => (float) $totals->sum('balance_sum'),
            'overdue' => (float) $totals->sum('overdue_sum'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Client::query()->withAccountTotals())
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Cliente')
                    ->searchable()->sortable()
                    ->description(fn (Client $r) => $r->document_number
                        ? strtoupper($r->document_type ?? '').' '.$r->document_number
                        : $r->email),

                Tables\Columns\TextColumn::make('invoiced_sum')->label('Facturado')
                    ->money('PEN')->sortable()->alignRight(),

                Tables\Columns\TextColumn::make('collected_sum')->label('Cobrado')
                    ->money('PEN')->sortable()->alignRight()->color('success'),

                Tables\Columns\TextColumn::make('balance_sum')->label('Saldo')
                    ->money('PEN')->sortable()->alignRight()->weight('bold')
                    ->color(fn ($state) => (float) $state > 0 ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('overdue_sum')->label('Vencido')
                    ->money('PEN')->sortable()->alignRight()->weight('bold')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('last_payment_at')->label('Último cobro')
                    ->date()->sortable()->placeholder('Nunca'),

                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (Client $r) => match ($r->status) {
                        'activo' => 'success',
                        'deudor' => 'danger',
                        'suspendido' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('con_saldo')->label('Solo con saldo')
                    ->query(fn ($query) => $query->whereHas(
                        'invoices',
                        fn ($q) => $q->outstanding(),
                    ))
                    ->default(),
                Tables\Filters\Filter::make('vencido')->label('Solo con deuda vencida')
                    ->query(fn ($query) => $query->whereHas(
                        'invoices',
                        fn ($q) => $q->overdue(),
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('facturas')
                    ->label('Ver facturas')
                    ->icon('heroicon-m-document-text')
                    ->url(fn (Client $r) => InvoiceResource::getUrl('index', [
                        'tableSearch' => $r->name,
                    ])),
                Tables\Actions\Action::make('cliente')
                    ->label('Abrir cliente')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->url(fn (Client $r) => ClientResource::getUrl('edit', ['record' => $r])),
            ])
            ->defaultSort('overdue_sum', 'desc')
            ->emptyStateHeading('Nadie debe nada')
            ->emptyStateDescription('Ningún cliente tiene facturas con saldo pendiente.');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view report') ?? false;
    }
}
