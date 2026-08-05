@php
    $icons = [
        'Servicio' => '<rect x="3" y="4" width="18" height="8" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 8h.01M7 17h.01"/>',
        'Dominio' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"/>',
        'Licencia' => '<circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 8.2-8.2"/><path d="m17 6 2 2M15 8l2 2"/>',
    ];
@endphp

<x-portal-shell :client="$client" active="renewals" title="Centro de renovaciones">
    <div class="mx-auto max-w-6xl">
        {{-- Encabezado --}}
        <div>
            <p class="ys-eyebrow">Centro de renovaciones</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">Lo que vence pronto</h1>
            <p class="mt-1 text-muted">
                Todo lo que hay que renovar en los próximos {{ $horizonDays }} días, junto en un solo lugar.
            </p>
        </div>

        {{-- Resumen --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3">
            <div class="zp-stat">
                <p class="zp-stat__label">Por renovar</p>
                <p class="zp-stat__value">{{ $items->count() }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Ya vencidos</p>
                <p @class(['zp-stat__value', 'text-red-600' => $expiredCount > 0])>{{ $expiredCount }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Costo estimado</p>
                <p class="zp-stat__value zp-num">S/ {{ number_format($total, 2) }}</p>
                @if ($pendingBalance > 0)
                    <p class="zp-num mt-0.5 text-xs font-medium text-red-600">
                        + S/ {{ number_format($pendingBalance, 2) }} de saldo pendiente
                    </p>
                @endif
            </div>
        </div>

        @if ($pendingBalance > 0)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <p class="text-sm font-medium text-amber-800">
                    Tienes S/ {{ number_format($pendingBalance, 2) }} pendientes de pago. Renovar con saldo al día evita cortes.
                </p>
                <a href="{{ url('/panel/pagos') }}" class="ys-btn ys-btn--primary !py-2">Ver mis pagos</a>
            </div>
        @endif

        {{-- Listado --}}
        <section class="ys-card mt-6 overflow-hidden !p-0">
            @if ($items->isEmpty())
                <div class="zp-empty">
                    <span class="ys-icon mx-auto">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <p class="mt-4 font-semibold text-ink">No hay nada por renovar</p>
                    <p class="mt-1 text-sm text-muted">
                        Nada tuyo vence en los próximos {{ $horizonDays }} días. Te avisaremos con tiempo.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Tipo</th>
                                <th>Vence</th>
                                <th class="text-right">Renovación</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $expired = $item['expires_at']?->isPast();
                                    $days = $item['expires_at'] ? (int) now()->startOfDay()->diffInDays($item['expires_at'], false) : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <span class="ys-icon !size-9 !rounded-lg">
                                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                                    stroke-linecap="round" stroke-linejoin="round">{!! $icons[$item['type']] !!}</svg>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-ink">{{ $item['name'] }}</p>
                                                @if ($item['detail'])
                                                    <p class="truncate text-xs text-muted">{{ $item['detail'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ $item['type'] }}</td>
                                    <td>
                                        <p @class(['font-medium text-red-600' => $expired, 'text-ink' => ! $expired])>
                                            {{ $item['expires_at']?->format('d/m/Y') ?? '—' }}
                                        </p>
                                        @if ($days !== null)
                                            <p @class(['text-xs', 'text-red-600' => $expired, 'text-muted' => ! $expired])>
                                                @if ($expired)
                                                    Venció hace {{ abs($days) }} {{ abs($days) === 1 ? 'día' : 'días' }}
                                                @elseif ($days === 0)
                                                    Vence hoy
                                                @else
                                                    En {{ $days }} {{ $days === 1 ? 'día' : 'días' }}
                                                @endif
                                            </p>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <p class="zp-num font-medium text-ink">
                                            {{ $item['price'] > 0 ? 'S/ '.number_format($item['price'], 2) : '—' }}
                                        </p>
                                        @if ($item['auto_renew'])
                                            <p class="text-xs text-muted">Automática</p>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ $item['url'] }}" class="ys-link">Ver detalle →</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="border-t border-line bg-canvas px-6 py-4 text-xs text-muted">
                    Los importes son referenciales. La renovación se cobra con la factura que emitimos antes del vencimiento;
                    lo marcado como automático se renueva solo si tu cuenta está al día.
                </p>
            @endif
        </section>
    </div>
</x-portal-shell>
