<?php

namespace App\Filament\Client\Widgets;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $clientId = auth()->user()?->client?->id ?? 0;

        $nextService = Service::where('client_id', $clientId)
            ->where('status', 'activo')
            ->whereNotNull('ends_at')
            ->orderBy('ends_at')
            ->first();

        return [
            Stat::make('Servicios activos', Service::where('client_id', $clientId)->where('status', 'activo')->count())
                ->color('success')
                ->icon('heroicon-o-server-stack'),

            Stat::make('Facturas pendientes', Invoice::where('client_id', $clientId)
                ->whereIn('status', ['pendiente', 'parcial', 'vencido'])->count())
                ->color('warning')
                ->icon('heroicon-o-document-text'),

            Stat::make('Tickets abiertos', Ticket::where('client_id', $clientId)
                ->whereIn('status', ['abierto', 'en_proceso', 'esperando_cliente'])->count())
                ->color('info')
                ->icon('heroicon-o-lifebuoy'),

            Stat::make('Próximo vencimiento', $nextService?->ends_at?->format('d/m/Y') ?? '—')
                ->description($nextService?->name ?? 'Sin servicios próximos')
                ->color('primary')
                ->icon('heroicon-o-calendar-days'),

            Stat::make('Dominios por vencer', Domain::where('client_id', $clientId)
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', now()->addDays(30))->count())
                ->color('warning')
                ->icon('heroicon-o-globe-alt'),
        ];
    }
}
