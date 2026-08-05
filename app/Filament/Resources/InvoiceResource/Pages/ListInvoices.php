<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\BillingRunner;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Lo mismo que hace el comando programado cada mañana, pero a
            // demanda: primero muestra qué se va a cobrar y a quién.
            Actions\Action::make('facturar_periodo')
                ->label('Facturar periodo')
                ->icon('heroicon-m-calendar-days')
                ->color('success')
                ->modalHeading('Emitir facturas de renovación')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitActionLabel('Emitir facturas')
                ->form([
                    Forms\Components\TextInput::make('days')
                        ->label('Anticipación')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(120)
                        ->suffix('días')
                        ->default(BillingRunner::LEAD_DAYS)
                        ->required()
                        ->live(debounce: 500)
                        ->helperText('Se factura lo que vence dentro de este plazo.'),

                    Forms\Components\Placeholder::make('preview')
                        ->label('Se emitirá')
                        ->content(function (Forms\Get $get) {
                            $days = (int) ($get('days') ?: BillingRunner::LEAD_DAYS);
                            $billing = app(BillingRunner::class);
                            $services = $billing->dueServices(now()->addDays($days));

                            if ($services->isEmpty()) {
                                return 'Nada por facturar en ese plazo.';
                            }

                            $lines = $services
                                ->groupBy(fn ($service) => $service->client?->name ?? 'Sin cliente')
                                ->map(fn ($group, $client) => sprintf(
                                    '%s — %d %s · S/ %s',
                                    $client,
                                    $group->count(),
                                    $group->count() === 1 ? 'servicio' : 'servicios',
                                    number_format((float) $group->sum('price'), 2),
                                ))
                                ->join("\n");

                            return sprintf(
                                "%d facturas por S/ %s\n\n%s",
                                $services->groupBy('client_id')->count(),
                                number_format((float) $services->sum('price'), 2),
                                $lines,
                            );
                        }),
                ])
                ->action(function (array $data) {
                    $invoices = app(BillingRunner::class)->run(now()->addDays((int) $data['days']));

                    if ($invoices->isEmpty()) {
                        Notification::make()
                            ->title('No había nada por facturar')
                            ->body('Ningún servicio vence en ese plazo sin factura emitida.')
                            ->warning()->send();

                        return;
                    }

                    Notification::make()
                        ->title($invoices->count().' '.($invoices->count() === 1 ? 'factura emitida' : 'facturas emitidas'))
                        ->body('Total: S/ '.number_format((float) $invoices->sum('total'), 2))
                        ->success()->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
