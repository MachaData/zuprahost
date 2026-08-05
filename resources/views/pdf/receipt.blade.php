@php
    /**
     * Recibo de pago y comprobante en PDF. DomPDF no entiende Tailwind ni
     * flexbox moderno, así que aquí se escribe CSS plano a propósito.
     */
    $asInvoice = $asInvoice ?? false;
    $company = config('apisperu.company');
    $title = $asInvoice
        ? ucfirst($invoice->documentLabel() === 'nota-de-venta' ? 'nota de venta' : $invoice->documentLabel())
        : 'Recibo de pago';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} {{ $invoice->documentNumber() }}</title>
    <style>
        @page { margin: 34px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #16233f; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        h1 { font-size: 20px; margin: 0; letter-spacing: -0.2px; }
        .head { width: 100%; border-bottom: 2px solid #16233f; padding-bottom: 14px; }
        .head td { vertical-align: top; }
        .brand { font-size: 17px; font-weight: bold; }
        .doc-no { font-size: 13px; font-weight: bold; margin-top: 3px; }
        .stamp {
            display: inline-block; margin-top: 8px; padding: 5px 12px; border-radius: 3px;
            background: #ecfdf5; color: #047857; font-weight: bold; font-size: 11px;
            border: 1px solid #a7f3d0;
        }
        .stamp--due { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .parties { width: 100%; margin-top: 22px; }
        .parties td { vertical-align: top; width: 50%; padding-right: 18px; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px; color: #6b7280; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 24px; }
        table.items th {
            text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px;
            color: #6b7280; border-bottom: 1px solid #e6e8ec; padding: 7px 8px;
        }
        table.items td { padding: 9px 8px; border-bottom: 1px solid #f1f2f4; }
        table.totals { width: 46%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        table.totals td { padding: 5px 8px; }
        table.totals tr.grand td { border-top: 1.5px solid #16233f; font-size: 13px; font-weight: bold; padding-top: 8px; }
        .section-title { margin: 26px 0 8px; font-size: 12px; font-weight: bold; }
        .foot { margin-top: 34px; border-top: 1px solid #e6e8ec; padding-top: 12px; font-size: 9.5px; color: #6b7280; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <p class="brand">{{ $company['razon_social'] ?? config('app.name') }}</p>
                @if (! empty($company['ruc']))
                    <p class="muted">RUC {{ $company['ruc'] }}</p>
                @endif
                @if (! empty($company['address']['direccion']))
                    <p class="muted">{{ $company['address']['direccion'] }}</p>
                @endif
            </td>
            <td class="right">
                <h1>{{ $title }}</h1>
                <p class="doc-no">{{ $invoice->documentNumber() }}</p>
                <p class="muted">Emitido: {{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}</p>

                @if ($balance <= 0)
                    <span class="stamp">PAGADO</span>
                @else
                    <span class="stamp stamp--due">SALDO S/ {{ number_format($balance, 2) }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <p class="label">Cliente</p>
                <p style="font-weight: bold;">{{ $invoice->billing_name ?: $client->name }}</p>
                @php
                    $docType = $invoice->billing_document_type ?: $client->document_type;
                    $docNumber = $invoice->billing_document_number ?: $client->document_number;
                @endphp
                @if ($docNumber)
                    <p class="muted">{{ strtoupper($docType ?? '') }} {{ $docNumber }}</p>
                @endif
                @if ($invoice->billing_address ?: $client->address)
                    <p class="muted">{{ $invoice->billing_address ?: $client->address }}</p>
                @endif
                <p class="muted">{{ $invoice->billing_email ?: $client->email }}</p>
            </td>
            <td class="right">
                @if ($invoice->periodLabel())
                    <p class="label">Periodo facturado</p>
                    <p>{{ $invoice->periodLabel() }}</p>
                    <p class="label" style="margin-top: 10px;">Vencimiento</p>
                @else
                    <p class="label">Vencimiento</p>
                @endif
                <p>{{ $invoice->due_at?->format('d/m/Y') ?? '—' }}</p>
                <p class="label" style="margin-top: 10px;">Referencia interna</p>
                <p>{{ $invoice->code }}</p>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="right" style="width: 12%;">Cant.</th>
                <th class="right" style="width: 19%;">P. unitario</th>
                <th class="right" style="width: 19%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="right">S/ {{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">S/ {{ number_format((float) $item->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Sin ítems registrados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="muted">Subtotal</td>
            <td class="right">S/ {{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="muted">IGV (18%)</td>
            <td class="right">S/ {{ number_format((float) $invoice->tax, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Total</td>
            <td class="right">S/ {{ number_format((float) $invoice->total, 2) }}</td>
        </tr>
    </table>

    @if ($invoice->payments->isNotEmpty())
        <p class="section-title">Pagos aplicados</p>
        <table class="items">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Método</th>
                    <th>N.° de operación</th>
                    <th class="right" style="width: 22%;">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ ucfirst($payment->method ?? '—') }}</td>
                        <td>{{ $payment->operation_code ?? '—' }}</td>
                        <td class="right">S/ {{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="3" class="right" style="font-weight: bold;">Total pagado</td>
                    <td class="right" style="font-weight: bold;">S/ {{ number_format($paid, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="foot">
        @if ($asInvoice)
            <p>Comprobante electrónico emitido conforme a las normas de SUNAT.</p>
        @else
            <p>
                Este recibo acredita el pago recibido. No es un comprobante electrónico:
                si necesitas factura o boleta, solicítala desde tu portal en Pagos.
            </p>
        @endif
        <p style="margin-top: 5px;">
            {{ config('app.name') }} · Documento generado el {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>
</body>
</html>
