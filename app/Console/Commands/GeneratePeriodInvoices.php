<?php

namespace App\Console\Commands;

use App\Services\BillingRunner;
use Illuminate\Console\Command;

class GeneratePeriodInvoices extends Command
{
    protected $signature = 'app:generate-period-invoices
                            {--days= : Días de anticipación (por defecto 15)}
                            {--dry-run : Muestra qué se facturaría sin crear nada}';

    protected $description = 'Emite las facturas de renovación de los servicios que vencen pronto.';

    public function handle(BillingRunner $billing): int
    {
        $days = (int) ($this->option('days') ?: BillingRunner::LEAD_DAYS);
        $upTo = now()->addDays($days);

        $services = $billing->dueServices($upTo);

        if ($services->isEmpty()) {
            $this->info("No hay servicios por facturar en los próximos {$days} días.");

            return self::SUCCESS;
        }

        $this->table(
            ['Cliente', 'Servicio', 'Periodo', 'Importe'],
            $services->map(fn ($service) => [
                $service->client?->name,
                $service->name,
                $billing->periodStart($service)->format('d/m/Y').' – '.$billing->periodEnd($service)->format('d/m/Y'),
                'S/ '.number_format((float) $service->price, 2),
            ]),
        );

        if ($this->option('dry-run')) {
            $this->comment('Simulación: no se creó ninguna factura.');

            return self::SUCCESS;
        }

        $invoices = $billing->run($upTo);

        $this->info(sprintf(
            '%d %s emitidas por S/ %s.',
            $invoices->count(),
            $invoices->count() === 1 ? 'factura' : 'facturas',
            number_format((float) $invoices->sum('total'), 2),
        ));

        return self::SUCCESS;
    }
}
