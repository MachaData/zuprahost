@php
    $paymentStatus = [
        'aprobado' => ['bg-emerald-50 text-emerald-700', 'Validado'],
        'pendiente' => ['bg-amber-50 text-amber-700', 'En revisión'],
        'rechazado' => ['bg-red-50 text-red-700', 'Rechazado'],
    ];
@endphp

<x-portal-shell :client="$client" active="payments" title="Comprobante {{ $invoice->code }}">
    <div class="mx-auto max-w-4xl">
        {{-- Volver --}}
        <a href="{{ route('panel.payments') }}" class="ys-link mb-4">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Volver a mis pagos
        </a>

        @if (session('status'))
            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                <svg class="mt-0.5 size-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>
                <p class="text-sm font-medium text-emerald-800">{{ session('status') }}</p>
            </div>
        @endif

        {{-- Comprobante saldado: lo primero que busca quien ya pagó --}}
        @if ($balance <= 0 && $paid > 0)
            <section class="mb-6 rounded-2xl border border-line bg-white px-6 py-8 text-center sm:px-10"
                style="box-shadow: var(--shadow-card)">
                <span class="relative mx-auto flex size-16 items-center justify-center rounded-xl bg-slate-100">
                    <svg class="size-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/>
                    </svg>
                    <span class="absolute -bottom-1 -right-1 flex size-6 items-center justify-center rounded-full bg-emerald-500 ring-4 ring-white">
                        <svg class="size-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"
                            stroke-linecap="round" stroke-linejoin="round"><path d="m5 13 4 4L19 7"/></svg>
                    </span>
                </span>

                <p class="mt-5 text-sm text-muted">Factura pagada</p>
                <p class="zp-num mt-1 text-4xl font-extrabold tracking-tight text-ink">
                    S/ {{ number_format((float) $invoice->total, 2) }}
                </p>

                <dl class="mx-auto mt-8 max-w-sm space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Número de factura</dt>
                        <dd class="font-medium text-ink">{{ $invoice->code }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Fecha de pago</dt>
                        <dd class="font-medium text-ink">{{ $invoice->paidAt()?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Método de pago</dt>
                        <dd class="font-medium text-ink">
                            {{ ucfirst($invoice->payments->firstWhere('status', 'aprobado')?->method ?? '—') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('panel.receipt', $invoice) }}" class="ys-btn border border-line bg-white text-ink hover:bg-slate-50">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 12 5 5 5-5"/><path d="M5 21h14"/></svg>
                        Descargar recibo
                    </a>

                    @if ($invoice->isElectronicallyIssued() || $invoice->hasAttachment())
                        <a href="{{ route('panel.invoice.document', $invoice) }}" class="ys-btn ys-btn--primary">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 12 5 5 5-5"/><path d="M5 21h14"/></svg>
                            {{ $invoice->attachment_name ?: 'Descargar '.$invoice->documentLabel() }}
                        </a>
                    @elseif ($canRequestInvoice)
                        <button type="button" class="ys-btn ys-btn--primary"
                            onclick="document.getElementById('zp-invoice-request').showModal()">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/></svg>
                            Solicitar factura
                        </button>
                    @elseif ($invoice->invoiceWasRequested())
                        <span class="zp-badge bg-brand-50 !px-4 !py-2.5 text-brand-700">
                            Factura solicitada el {{ $invoice->invoice_requested_at->format('d/m/Y') }} · la estamos emitiendo
                        </span>
                    @endif
                </div>

                @if ($canRequestInvoice)
                    <p class="mt-4 text-xs text-muted">
                        ¿Necesitas factura con RUC para tu contabilidad? Pídela aquí y la emitimos ante SUNAT.
                    </p>
                @endif
            </section>
        @endif

        <section class="ys-card !p-0 overflow-hidden">
            {{-- Cabecera --}}
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line px-7 py-6">
                <div>
                    <p class="ys-eyebrow">{{ ucfirst(str_replace('_', ' ', $invoice->document_type ?? 'comprobante')) }}</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-ink">{{ $invoice->code }}</h1>
                </div>
                <div class="text-right">
                    <x-status-badge :status="$invoice->status" class="!px-4 !py-1.5 !text-sm"/>
                    @if ($balance > 0)
                        <p class="zp-num mt-2 text-sm text-muted">
                            Saldo: <span class="font-semibold text-ink">S/ {{ number_format($balance, 2) }}</span>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Emisor y receptor --}}
            <div class="grid gap-6 border-b border-line px-7 py-6 sm:grid-cols-2">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-muted">De</p>
                    <p class="mt-1 font-bold text-ink">{{ config('app.name') }}</p>
                    <p class="text-sm text-muted">Servicios digitales · Perú</p>
                </div>
                <div class="sm:text-right">
                    <p class="text-[11px] uppercase tracking-wide text-muted">Facturado a</p>
                    <p class="mt-1 font-bold text-ink">{{ $client->name }}</p>
                    @if ($client->document_number)
                        <p class="text-sm text-muted">
                            {{ strtoupper($client->document_type ?? '') }} {{ $client->document_number }}
                        </p>
                    @endif
                    <p class="mt-2 text-sm text-muted">
                        Emitida: {{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}<br>
                        Vence: {{ $invoice->due_at?->format('d/m/Y') ?? '—' }}
                        @if ($invoice->periodLabel())
                            <br>Periodo: <span class="font-medium text-ink">{{ $invoice->periodLabel() }}</span>
                        @endif
                    </p>
                </div>
            </div>

            {{-- Ítems --}}
            <div class="overflow-x-auto">
                <table class="zp-table">
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th class="text-right">Cant.</th>
                            <th class="text-right">P. unitario</th>
                            <th class="text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td class="text-ink">{{ $item->description }}</td>
                                <td class="zp-num text-right text-muted">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                <td class="zp-num text-right text-muted">S/ {{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="zp-num text-right font-medium">S/ {{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Sin ítems registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totales --}}
            <div class="flex justify-end border-t border-line px-7 py-5">
                <dl class="w-full max-w-xs space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted">Subtotal</dt>
                        <dd class="zp-num font-medium text-ink">S/ {{ number_format((float) $invoice->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted">IGV</dt>
                        <dd class="zp-num font-medium text-ink">S/ {{ number_format((float) $invoice->tax, 2) }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-line pt-2">
                        <dt class="font-semibold text-ink">Total</dt>
                        <dd class="zp-num text-base font-bold text-ink">S/ {{ number_format((float) $invoice->total, 2) }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Cómo pagar --}}
        @if ($methods->isNotEmpty() || $paymentLinks->isNotEmpty())
            <section class="ys-card mt-6 !p-0 overflow-hidden">
                <div class="border-b border-line px-7 py-4">
                    <h2 class="font-bold text-ink">Cómo pagar</h2>
                    <p class="mt-0.5 text-sm text-muted">
                        Paga S/ {{ number_format($balance, 2) }} por cualquiera de estos medios.
                    </p>
                </div>

                {{-- Enlaces de pago: solo los servicios que tienen uno cargado --}}
                @if ($paymentLinks->isNotEmpty())
                    <div class="border-b border-line bg-brand-50/40 px-7 py-5">
                        <p class="text-sm font-semibold text-ink">Pagar en línea</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            @foreach ($paymentLinks as $link)
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"
                                    class="ys-btn ys-btn--primary !py-2.5">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>
                                    </svg>
                                    {{ $link['name'] }}
                                </a>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-muted">Se abre la pasarela en una pestaña nueva.</p>
                    </div>
                @endif

                <div class="divide-y divide-line">
                    @foreach ($methods as $method)
                        @php $details = $method->accountDetails(); @endphp
                        <div class="flex flex-col gap-4 px-7 py-5 sm:flex-row">
                            <span class="ys-icon shrink-0">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">{!! $method->icon() !!}</svg>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-ink">{{ $method->name }}</p>

                                @if ($method->instructions)
                                    <p class="mt-1 text-sm leading-relaxed text-muted">{{ $method->instructions }}</p>
                                @endif

                                @if ($details)
                                    <dl class="mt-3 space-y-1.5 text-sm">
                                        @foreach ($details as $label => $value)
                                            <div class="flex flex-wrap items-center gap-x-2">
                                                <dt class="text-muted">{{ $label }}:</dt>
                                                <dd><span class="zp-key">{{ $value }}</span></dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @endif

                                @if ($method->kind === 'enlace' && $method->url)
                                    <a href="{{ $method->url }}" target="_blank" rel="noopener noreferrer"
                                        class="ys-link mt-3">Ir a pagar →</a>
                                @endif
                            </div>

                            @if ($method->qr_path)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($method->qr_path) }}"
                                    alt="Código QR de {{ $method->name }}"
                                    class="size-28 shrink-0 self-start rounded-xl border border-line object-contain p-1">
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($methods->contains(fn ($m) => $m->requires_receipt))
                    <p class="border-t border-line bg-canvas px-7 py-4 text-xs text-muted">
                        Después de pagar, sube tu constancia para que la validemos. Hasta entonces la factura sigue como pendiente.
                    </p>
                @endif
            </section>
        @endif

        {{-- Comprobantes aplicados --}}
        <section class="ys-card mt-6 !p-0 overflow-hidden">
            <div class="border-b border-line px-7 py-4">
                <h2 class="font-bold text-ink">Pagos aplicados</h2>
                <p class="mt-0.5 text-sm text-muted">Comprobantes que enviaste para esta factura.</p>
            </div>

            @if ($invoice->payments->isEmpty())
                <div class="zp-empty">
                    <p class="text-sm text-muted">Todavía no has enviado ningún comprobante para esta factura.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Método</th>
                                <th>N.° de operación</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->payments as $payment)
                                @php [$badge, $label] = $paymentStatus[$payment->status] ?? ['bg-slate-100 text-slate-600', ucfirst($payment->status)]; @endphp
                                <tr>
                                    <td class="text-muted">{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-ink">{{ ucfirst($payment->method ?? '—') }}</td>
                                    <td class="text-muted">{{ $payment->operation_code ?? '—' }}</td>
                                    <td class="zp-num text-right font-medium">S/ {{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="text-right">
                                        <span class="zp-badge {{ $badge }}">{{ $label }}</span>
                                        @if ($payment->status === 'rechazado' && $payment->admin_note)
                                            <p class="mt-1 text-xs text-red-600">{{ $payment->admin_note }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Saldo y acción --}}
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-line bg-canvas px-7 py-5">
                <dl class="text-sm">
                    <dt class="text-muted">Pagado / Saldo</dt>
                    <dd class="zp-num mt-0.5 font-bold text-ink">
                        S/ {{ number_format($paid, 2) }}
                        <span class="text-muted">/</span>
                        S/ {{ number_format($balance, 2) }}
                    </dd>
                </dl>

                @if ($balance > 0 && $invoice->status !== 'anulado')
                    <a href="{{ url('/client/invoices') }}" class="ys-btn ys-btn--primary">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></svg>
                        Subir comprobante
                    </a>
                @else
                    <span class="zp-badge bg-emerald-50 text-emerald-700">Sin saldo pendiente</span>
                @endif
            </div>
        </section>

        {{-- Solicitud de comprobante electrónico --}}
        @if ($canRequestInvoice)
            <dialog id="zp-invoice-request" class="zp-dialog">
                <form method="POST" action="{{ route('panel.invoice.request', $invoice) }}" class="p-7">
                    @csrf

                    <h2 class="text-lg font-extrabold tracking-tight text-ink">Solicitar factura</h2>
                    <p class="mt-1 text-sm text-muted">
                        Emitimos tu comprobante ante SUNAT con estos datos. Revísalos bien: una vez emitido no se puede cambiar.
                    </p>

                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <ul class="space-y-1 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="zp-doc-type" class="zp-label">Tipo de documento</label>
                            <select id="zp-doc-type" name="billing_document_type" class="zp-input" required>
                                <option value="ruc" @selected(old('billing_document_type', $client->document_type) === 'ruc')>RUC — factura</option>
                                <option value="dni" @selected(old('billing_document_type', $client->document_type) === 'dni')>DNI — boleta</option>
                                <option value="ce" @selected(old('billing_document_type', $client->document_type) === 'ce')>Carné de extranjería — boleta</option>
                            </select>
                        </div>

                        <div>
                            <label for="zp-doc-number" class="zp-label">Número de documento</label>
                            <input id="zp-doc-number" name="billing_document_number" class="zp-input" required
                                inputmode="numeric" placeholder="20512345678"
                                value="{{ old('billing_document_number', $client->document_number) }}">
                            <p class="mt-1 text-xs text-muted">RUC: 11 dígitos · DNI: 8 dígitos</p>
                        </div>

                        <div>
                            <label for="zp-name" class="zp-label">Nombre o razón social</label>
                            <input id="zp-name" name="billing_name" class="zp-input" required
                                placeholder="Mi Empresa S.A.C."
                                value="{{ old('billing_name', $client->name) }}">
                        </div>

                        <div>
                            <label for="zp-address" class="zp-label">Dirección fiscal <span class="font-normal text-muted">(opcional)</span></label>
                            <input id="zp-address" name="billing_address" class="zp-input"
                                value="{{ old('billing_address', $client->address) }}">
                        </div>

                        <div>
                            <label for="zp-email" class="zp-label">Correo para enviarla <span class="font-normal text-muted">(opcional)</span></label>
                            <input id="zp-email" name="billing_email" type="email" class="zp-input"
                                value="{{ old('billing_email', $client->email) }}">
                        </div>
                    </div>

                    <div class="mt-7 flex justify-end gap-3">
                        <button type="button" class="ys-btn ys-btn--ghost"
                            onclick="document.getElementById('zp-invoice-request').close()">Cancelar</button>
                        <button type="submit" class="ys-btn ys-btn--primary">Enviar solicitud</button>
                    </div>
                </form>
            </dialog>

            {{-- Si la validación falló, el diálogo vuelve a abrirse con los errores --}}
            @if ($errors->any())
                <script>document.getElementById('zp-invoice-request').showModal()</script>
            @endif
        @endif
    </div>
</x-portal-shell>
