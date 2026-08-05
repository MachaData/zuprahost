@php
    // La banda inferior de cada tarjeta resume el estado de un vistazo.
    $bands = [
        'activo' => ['bg-emerald-600', 'Activo'],
        'por_vencer' => ['bg-amber-500', 'Por vencer'],
        'vencido' => ['bg-red-600', 'Vencido'],
        'suspendido' => ['bg-slate-500', 'Suspendido'],
    ];
@endphp

<x-portal-shell :client="$client" active="domains" title="Mis dominios">
    <div class="mx-auto max-w-6xl">
        {{-- Encabezado --}}
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="ys-eyebrow">Mis dominios</p>
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">
                    Dominios registrados <span class="text-muted">({{ $domains->total() }})</span>
                </h1>
                <p class="mt-1 text-muted">
                    @if ($expiringCount > 0)
                        {{ $expiringCount }} {{ $expiringCount === 1 ? 'dominio vence' : 'dominios vencen' }} en los próximos 30 días.
                    @else
                        Ningún dominio vence en los próximos 30 días.
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Cambio de vista: sin JavaScript, solo un parámetro en la URL --}}
                <div class="flex overflow-hidden rounded-xl border border-line bg-white" role="group" aria-label="Cambiar vista">
                    @foreach ([
                        'tarjetas' => ['Vista de tarjetas', '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],
                        'lista' => ['Vista de lista', '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>'],
                    ] as $key => [$aria, $icon])
                        <a href="{{ request()->fullUrlWithQuery(['vista' => $key]) }}"
                            aria-label="{{ $aria }}"
                            @if ($view === $key) aria-current="true" @endif
                            @class([
                                'flex size-10 items-center justify-center transition',
                                'bg-brand-600 text-white' => $view === $key,
                                'text-muted hover:bg-brand-50 hover:text-brand-700' => $view !== $key,
                            ])>
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                        </a>
                    @endforeach
                </div>

                <a href="{{ url('/contratar') }}" class="ys-btn ys-btn--primary">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    Buscar dominio
                </a>
            </div>
        </div>

        @if ($domains->isEmpty())
            <section class="ys-card mt-6 overflow-hidden !p-0">
                <div class="zp-empty">
                    <span class="ys-icon mx-auto">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18a15 15 0 0 1 0-18z"/>
                        </svg>
                    </span>
                    <p class="mt-4 font-semibold text-ink">Aún no tienes dominios</p>
                    <p class="mt-1 text-sm text-muted">Registra un .com, .pe u otra extensión para que te encuentren fácil.</p>
                    <a href="{{ url('/contratar') }}" class="ys-link mt-4 justify-center">Buscar un dominio →</a>
                </div>
            </section>

        @elseif ($view === 'tarjetas')
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($domains as $domain)
                    @php
                        [$band, $bandLabel] = $bands[$domain->status] ?? ['bg-slate-500', ucfirst($domain->status)];
                        $external = $domain->isExternal();
                    @endphp
                    <article class="flex flex-col overflow-hidden rounded-2xl border border-line bg-white transition hover:-translate-y-0.5 hover:border-brand-200"
                        style="box-shadow: var(--shadow-card)">
                        <div class="flex flex-1 flex-col items-center px-5 py-8 text-center">
                            <h2 class="break-all text-lg font-bold text-ink">{{ $domain->name }}</h2>

                            <p @class([
                                'mt-2 text-sm',
                                'font-medium text-red-600' => $domain->expires_at?->isPast(),
                                'text-muted' => ! $domain->expires_at?->isPast(),
                            ])>
                                {{ $domain->expires_at ? 'Vence el '.$domain->expires_at->format('d/m/Y') : 'Sin fecha de vencimiento' }}
                            </p>

                            <p class="mt-1 text-xs text-muted">{{ $domain->originLabel() }}</p>

                            <div class="mt-4 flex flex-wrap justify-center gap-2">
                                @if ($domain->renewal_price && $domain->renewal_managed)
                                    <span class="zp-badge bg-slate-100 text-ink-soft zp-num">
                                        Renovación S/ {{ number_format((float) $domain->renewal_price, 2) }}
                                    </span>
                                @endif
                                @if ($domain->auto_renew)
                                    <span class="zp-badge bg-brand-50 text-brand-700">Renovación automática</span>
                                @endif
                                @if ($domain->whois_protection)
                                    <span class="zp-badge bg-emerald-50 text-emerald-700">WHOIS protegido</span>
                                @endif
                            </div>
                        </div>

                        <p class="{{ $external ? 'bg-sky-600' : $band }} px-4 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-white">
                            {{ $external ? 'Dominio externo' : $bandLabel }}
                        </p>
                    </article>
                @endforeach
            </div>

        @else
            <section class="ys-card mt-6 overflow-hidden !p-0">
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Dominio</th>
                                <th>Proveedor y origen</th>
                                <th class="text-right">Renovación</th>
                                <th>Vence</th>
                                <th class="text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($domains as $domain)
                                <tr>
                                    <td>
                                        <p class="font-medium text-ink">{{ $domain->name }}</p>
                                        @if ($domain->nameservers)
                                            <p class="mt-0.5 truncate text-xs text-muted" title="{{ $domain->nameservers }}">
                                                {{ \Illuminate\Support\Str::limit($domain->nameservers, 44) }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="text-muted">
                                        {{ $domain->provider ?? '—' }}
                                        <span class="mt-0.5 block text-xs">{{ $domain->originLabel() }}</span>
                                    </td>
                                    <td class="zp-num text-right font-medium">
                                        {{ $domain->renewal_price ? 'S/ '.number_format((float) $domain->renewal_price, 2) : '—' }}
                                    </td>
                                    <td @class(['text-red-600 font-medium' => $domain->expires_at?->isPast(), 'text-muted' => ! $domain->expires_at?->isPast()])>
                                        {{ $domain->expires_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="text-right"><x-status-badge :status="$domain->status"/></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($domains->hasPages())
            <div class="mt-6">{{ $domains->appends(['vista' => $view])->links() }}</div>
        @endif
    </div>
</x-portal-shell>
