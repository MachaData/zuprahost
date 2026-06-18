<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente para la facturación electrónica vía APISPERU (SUNAT).
 *
 * Documentación: https://apisperu.com  ·  https://facturacion.apisperu.com
 */
class ApisPeruService
{
    public function token(): ?string
    {
        return Setting::get('apisperu_token', config('apisperu.token'));
    }

    public function baseUrl(): string
    {
        return rtrim(Setting::get('apisperu_base_url', config('apisperu.base_url')), '/');
    }

    public function isEnabled(): bool
    {
        return (bool) (Setting::get('apisperu_enabled', config('apisperu.enabled')))
            && ! empty($this->token());
    }

    /**
     * Build the SUNAT payload from an Invoice + its client and items.
     *
     * @return array<string, mixed>
     */
    public function buildPayload(Invoice $invoice): array
    {
        $invoice->loadMissing(['client', 'items']);
        $client = $invoice->client;

        $tipoDoc = $invoice->document_type === 'factura' ? '01' : '03'; // 01=Factura, 03=Boleta
        $tipoDocCliente = $invoice->document_type === 'factura' ? '6' : '1'; // 6=RUC, 1=DNI

        $igvRate = (float) config('apisperu.igv_rate', 0.18);

        $details = $invoice->items->map(function ($item) use ($igvRate) {
            $valorUnitario = round((float) $item->unit_price / (1 + $igvRate), 2);
            $cantidad = (float) $item->quantity;
            $valorVenta = round($valorUnitario * $cantidad, 2);
            $igv = round($valorVenta * $igvRate, 2);

            return [
                'codProducto' => (string) ($item->service_id ?? $item->id),
                'unidad' => 'NIU',
                'descripcion' => $item->description,
                'cantidad' => $cantidad,
                'mtoValorUnitario' => $valorUnitario,
                'mtoValorVenta' => $valorVenta,
                'mtoBaseIgv' => $valorVenta,
                'porcentajeIgv' => $igvRate * 100,
                'igv' => $igv,
                'tipAfeIgv' => 10, // Gravado - Operación Onerosa
                'totalImpuestos' => $igv,
                'mtoPrecioUnitario' => (float) $item->unit_price,
            ];
        })->values()->all();

        $company = config('apisperu.company');

        return [
            'ublVersion' => '2.1',
            'tipoOperacion' => '0101',
            'tipoDoc' => $tipoDoc,
            'serie' => $invoice->series ?: ($tipoDoc === '01' ? 'F001' : 'B001'),
            'correlativo' => $invoice->number ?: (string) $invoice->id,
            'fechaEmision' => ($invoice->issued_at ?? now())->toAtomString(),
            'formaPago' => ['moneda' => 'PEN', 'tipo' => 'Contado'],
            'tipoMoneda' => 'PEN',
            'company' => [
                'ruc' => $company['ruc'],
                'razonSocial' => $company['razon_social'],
                'nombreComercial' => $company['nombre_comercial'],
                'address' => $company['address'],
            ],
            'client' => [
                'tipoDoc' => $tipoDocCliente,
                'numDoc' => $client->document_number,
                'rznSocial' => $client->name,
            ],
            'mtoOperGravadas' => (float) $invoice->subtotal,
            'mtoIGV' => (float) $invoice->tax,
            'totalImpuestos' => (float) $invoice->tax,
            'valorVenta' => (float) $invoice->subtotal,
            'subTotal' => (float) $invoice->total,
            'mtoImpVenta' => (float) $invoice->total,
            'details' => $details,
            'legends' => [
                ['code' => '1000', 'value' => 'SON '.strtoupper(number_format((float) $invoice->total, 2)).' SOLES'],
            ],
        ];
    }

    /**
     * Send the invoice to SUNAT through APISPERU. Stores the result on the invoice.
     */
    public function send(Invoice $invoice): array
    {
        if ($invoice->document_type === 'nota_venta') {
            throw new RuntimeException('Las notas de venta no se envían a SUNAT.');
        }

        $payload = $this->buildPayload($invoice);

        if (! $this->isEnabled()) {
            // Modo simulación: no se hace la llamada real, pero se registra el intento.
            $invoice->update([
                'sunat_status' => 'enviado',
                'sunat_response' => ['simulated' => true, 'payload' => $payload],
            ]);

            return ['simulated' => true];
        }

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->post($this->baseUrl().'/invoice/send', $payload);

        $data = $response->json() ?? [];

        if (! $response->successful()) {
            $invoice->update([
                'sunat_status' => 'rechazado',
                'sunat_response' => $data,
            ]);

            throw new RuntimeException($data['message'] ?? 'Error al comunicarse con APISPERU.');
        }

        $accepted = (bool) ($data['sunatResponse']['success'] ?? false);

        $invoice->update([
            'sunat_status' => $accepted ? 'aceptado' : 'rechazado',
            'sunat_response' => $data,
        ]);

        return $data;
    }
}
