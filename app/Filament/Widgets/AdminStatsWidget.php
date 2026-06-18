<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $monthIncome = Payment::where('status', 'aprobado')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        return [
            Stat::make('Clientes', Client::count())
                ->description('Total de clientes')
                ->color('primary')
                ->icon('heroicon-o-users'),

            Stat::make('Servicios activos', Service::where('status', 'activo')->count())
                ->description('En funcionamiento')
                ->color('success')
                ->icon('heroicon-o-server-stack'),

            Stat::make('Facturas pendientes', Invoice::whereIn('status', ['pendiente', 'parcial'])->count())
                ->description('Por cobrar')
                ->color('warning')
                ->icon('heroicon-o-document-text'),

            Stat::make('Ingresos del mes', 'S/ '.number_format((float) $monthIncome, 2))
                ->description('Pagos aprobados')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Dominios por vencer', Domain::whereIn('status', ['por_vencer', 'activo'])
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', now()->addDays(30))->count())
                ->description('Próximos 30 días')
                ->color('warning')
                ->icon('heroicon-o-globe-alt'),

            Stat::make('Tickets abiertos', Ticket::whereIn('status', ['abierto', 'en_proceso'])->count())
                ->description('Requieren atención')
                ->color('info')
                ->icon('heroicon-o-lifebuoy'),

            Stat::make('Clientes deudores', Client::where('status', 'deudor')->count())
                ->description('Con saldo pendiente')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
