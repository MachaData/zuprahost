<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Facturación por periodos.
 *
 * Genera las facturas de renovación de los servicios que vencen dentro de la
 * ventana de anticipación, agrupando por cliente: un cliente con hosting,
 * dominio y correo recibe una sola factura con tres líneas, no tres facturas.
 */
class BillingRunner
{
    /**
     * Días de anticipación con los que se emite la factura de renovación.
     */
    public const LEAD_DAYS = 15;

    /**
     * Días que tiene el cliente para pagar desde la emisión.
     */
    public const DUE_DAYS = 7;

    /**
     * Servicios que toca facturar: activos, renovables, con fecha de fin
     * dentro de la ventana y sin una factura que ya cubra ese periodo.
     *
     * @return Collection<int, Service>
     */
    public function dueServices(?Carbon $upTo = null): Collection
    {
        $upTo ??= now()->addDays(self::LEAD_DAYS);

        return Service::query()
            ->with(['client', 'product'])
            ->whereIn('status', ['activo', 'vencido'])
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '<=', $upTo)
            ->where('price', '>', 0)
            ->get()
            ->reject(fn (Service $service) => $this->alreadyInvoiced($service));
    }

    /**
     * ¿Ya existe una factura viva que cubra el siguiente periodo del servicio?
     * Evita duplicar el cobro si el comando se corre dos veces el mismo día.
     */
    public function alreadyInvoiced(Service $service): bool
    {
        $periodStart = $this->periodStart($service);

        return Invoice::where('client_id', $service->client_id)
            ->where('status', '!=', 'anulado')
            ->whereDate('period_start', $periodStart)
            ->whereHas('items', fn ($q) => $q->where('service_id', $service->id))
            ->exists();
    }

    /**
     * Emite las facturas del periodo. Devuelve las creadas.
     *
     * @return Collection<int, Invoice>
     */
    public function run(?Carbon $upTo = null): Collection
    {
        return $this->dueServices($upTo)
            ->groupBy('client_id')
            ->map(fn (Collection $services, int $clientId) => $this->invoiceFor(
                Client::find($clientId),
                $services,
            ))
            ->filter()
            ->values();
    }

    /**
     * Una factura por cliente con una línea por servicio.
     *
     * @param  Collection<int, Service>  $services
     */
    public function invoiceFor(?Client $client, Collection $services): ?Invoice
    {
        if (! $client || $services->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($client, $services) {
            // El periodo de la factura abarca desde el primer servicio que
            // renueva hasta el último, para que la cabecera diga la verdad.
            $starts = $services->map(fn (Service $s) => $this->periodStart($s));
            $ends = $services->map(fn (Service $s) => $this->periodEnd($s));

            $invoice = Invoice::create([
                'client_id' => $client->id,
                'code' => Invoice::generateCode(),
                'issued_at' => today(),
                'due_at' => today()->addDays(self::DUE_DAYS),
                'period_start' => $starts->min(),
                'period_end' => $ends->max(),
                'status' => 'pendiente',
                'document_type' => $client->defaultDocumentType(),
            ]);

            foreach ($services as $service) {
                $invoice->items()->create([
                    'service_id' => $service->id,
                    'description' => sprintf(
                        '%s · %s a %s',
                        $service->name,
                        $this->periodStart($service)->format('d/m/Y'),
                        $this->periodEnd($service)->format('d/m/Y'),
                    ),
                    'quantity' => 1,
                    'unit_price' => (float) $service->price,
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice->refresh();
        });
    }

    /**
     * El nuevo periodo arranca cuando termina el vigente.
     */
    public function periodStart(Service $service): Carbon
    {
        return ($service->ends_at ?? today())->copy()->addDay()->startOfDay();
    }

    public function periodEnd(Service $service): Carbon
    {
        $start = $this->periodStart($service);

        return match ($service->billing_cycle) {
            'anual' => $start->copy()->addYear()->subDay(),
            'unico' => $start->copy()->addYear()->subDay(),
            default => $start->copy()->addMonth()->subDay(),
        };
    }
}
