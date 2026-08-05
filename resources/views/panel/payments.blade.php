<x-portal-shell :client="$client" active="payments" title="Pagos">
    <div class="mx-auto max-w-6xl">
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="ys-eyebrow">Pagos</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">Mis pagos</h1>
                <p class="mt-1 text-muted">
                    @if ($tab === 'pagadas')
                        Tus facturas cerradas. Descarga el recibo o pide tu comprobante electrónico.
                    @elseif ($pendingCount > 0)
                        Tienes {{ $pendingCount }} {{ $pendingCount === 1 ? 'factura por pagar' : 'facturas por pagar' }}.
                    @else
                        Estás al día. No tienes saldo pendiente.
                    @endif
                    @if ($awaitingReview > 0 && $tab !== 'pagadas')
                        <span class="text-brand-700">
                            {{ $awaitingReview }} {{ $awaitingReview === 1 ? 'comprobante está' : 'comprobantes están' }} en revisión.
                        </span>
                    @endif
                </p>
            </div>

            @if ($pendingBalance > 0 && $tab !== 'pagadas')
                <div class="zp-stat min-w-52">
                    <p class="zp-stat__label">Saldo pendiente</p>
                    <p class="zp-stat__value zp-num">S/ {{ number_format($pendingBalance, 2) }}</p>
                </div>
            @endif
        </div>

        {{-- Pestañas --}}
        <div class="mt-6 flex gap-1 border-b border-line" role="tablist">
            @foreach ([
                'por_pagar' => ['Por pagar', $pendingCount],
                'pagadas' => ['Pagadas', $settledCount],
            ] as $key => [$label, $count])
                <a href="{{ url('/panel/pagos').($key === 'pagadas' ? '?estado=pagadas' : '') }}"
                    role="tab"
                    @if ($tab === $key) aria-selected="true" @endif
                    @class([
                        '-mb-px flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition',
                        'border-brand-600 text-brand-700' => $tab === $key,
                        'border-transparent text-muted hover:text-ink' => $tab !== $key,
                    ])>
                    {{ $label }}
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs',
                        'bg-brand-50 text-brand-700' => $tab === $key,
                        'bg-slate-100 text-muted' => $tab !== $key,
                    ])>{{ $count }}</span>
                </a>
            @endforeach
        </div>

        {{-- Listado --}}
        <section class="ys-card mt-6 overflow-hidden !p-0">
            @if ($invoices->isEmpty())
                <div class="zp-empty">
                    <span class="ys-icon mx-auto">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>
                        </svg>
                    </span>
                    @if ($tab === 'pagadas')
                        <p class="mt-4 font-semibold text-ink">Todavía no tienes facturas pagadas</p>
                        <p class="mt-1 text-sm text-muted">Cuando validemos un pago, la factura aparecerá aquí con su recibo.</p>
                    @else
                        <p class="mt-4 font-semibold text-ink">No tienes nada por pagar</p>
                        <p class="mt-1 text-sm text-muted">Estás al día. Revisa la pestaña «Pagadas» para descargar comprobantes.</p>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Comprobante</th>
                                <th>Emisión</th>
                                <th>Vence</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Pagado</th>
                                <th class="text-right">Saldo</th>
                                <th>Estado</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                @php
                                    $paid = (float) ($invoice->paid_sum ?? 0);
                                    $balance = max(0, (float) $invoice->total - $paid);
                                    $payable = $balance > 0 && $invoice->status !== 'anulado';
                                    $overdue = $invoice->due_at?->isPast() && $payable;
                                @endphp
                                <tr>
                                    <td class="font-medium text-ink">
                                        <a href="{{ route('panel.payment', $invoice) }}" class="hover:text-brand-700">
                                            {{ $invoice->code }}
                                        </a>
                                    </td>
                                    <td class="text-muted">
                                        {{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}
                                        @if ($invoice->periodLabel())
                                            <span class="mt-0.5 block text-xs">{{ $invoice->periodLabel() }}</span>
                                        @endif
                                    </td>
                                    <td @class(['text-red-600 font-medium' => $overdue, 'text-muted' => ! $overdue])>
                                        {{ $invoice->due_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="zp-num text-right">S/ {{ number_format((float) $invoice->total, 2) }}</td>
                                    <td class="zp-num text-right text-emerald-700">S/ {{ number_format($paid, 2) }}</td>
                                    <td @class(['zp-num text-right font-semibold', 'text-ink' => $balance > 0, 'text-muted' => $balance <= 0])>
                                        S/ {{ number_format($balance, 2) }}
                                    </td>
                                    <td>
                                        <x-status-badge :status="$invoice->status"/>
                                        @if ($invoice->invoiceWasRequested() && ! $invoice->isElectronicallyIssued())
                                            <p class="mt-1 text-xs text-brand-700">Factura solicitada</p>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($paid > 0)
                                                <a href="{{ route('panel.receipt', $invoice) }}"
                                                    class="ys-btn ys-btn--ghost border border-line !px-3 !py-2 !text-xs"
                                                    title="Descargar recibo en PDF">
                                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 12 5 5 5-5"/><path d="M5 21h14"/></svg>
                                                    Recibo
                                                </a>
                                            @endif
                                            <a href="{{ route('panel.payment', $invoice) }}"
                                                class="ys-btn ys-btn--ghost border border-line !px-3 !py-2 !text-xs">
                                                Ver detalle
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($invoices->hasPages())
            <div class="mt-6">{{ $invoices->links() }}</div>
        @endif
    </div>
</x-portal-shell>
