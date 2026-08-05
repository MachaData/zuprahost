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

        // Se calcula factura por factura para no compensar la deuda de una
        // con lo pagado de más en otra.
        $outstanding = Invoice::outstanding()->get()->sum(fn (Invoice $i) => $i->balance());
        $overdue = Invoice::overdue()->get()->sum(fn (Invoice $i) => $i->balance());

        return [
            Stat::make('Clientes', Client::count())
                ->description('Total de clientes')
                ->color('primary')
                ->icon('heroicon-o-users'),

            Stat::make('Servicios activos', Service::where('status', 'activo')->count())
                ->description('En funcionamiento')
                ->color('success')
                ->icon('heroicon-o-server-stack'),

            Stat::make('Por cobrar', 'S/ '.number_format($outstanding, 2))
                ->description(Invoice::outstanding()->count().' facturas con saldo')
                ->color($outstanding > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-document-text'),

            Stat::make('Deuda vencida', 'S/ '.number_format($overdue, 2))
                ->description(Invoice::overdue()->count().' facturas pasadas de fecha')
                ->color($overdue > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-clock'),

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

            Stat::make('Clientes deudores', Client::whereHas('invoices', fn ($q) => $q->overdue())->count())
                ->description('Con facturas vencidas')
                ->color('danger')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
