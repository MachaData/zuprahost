@php
    // Tarjetas de "más maneras de crecer" → llevan a /contratar
    $grow = [
        ['title' => 'Encuentra un nuevo dominio', 'text' => 'Registra un .com, .pe u otra extensión para que te encuentren fácil.', 'cta' => 'Buscar dominio', 'icon' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>'],
        ['title' => 'Protege tu dominio', 'text' => 'La protección WHOIS mantiene tus datos privados y tu marca segura.', 'cta' => 'Proteger dominio', 'icon' => '<path d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7z"/>'],
        ['title' => 'Crea un correo profesional', 'text' => 'Usa una dirección con tu dominio para generar más confianza.', 'cta' => 'Crear correo', 'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'],
        ['title' => 'Haz crecer tu negocio', 'text' => 'Mantenimiento web y SEO mensual para más visibilidad en línea.', 'cta' => 'Ver planes', 'icon' => '<path d="M3 3v18h18"/><path d="m7 15 3-4 3 2 4-6"/>'],
    ];
@endphp

<x-portal-shell :client="$client" active="panel" title="Inicio">
    {{-- Saludo --}}
    <div class="mx-auto max-w-6xl">
        <p class="ys-eyebrow">Inicio</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">
            Hola, {{ \Illuminate\Support\Str::of($client->name)->explode(' ')->first() }} 👋
        </h1>
        <p class="mt-1 text-muted">Este es el resumen de tu cuenta en zupraHost.</p>

        {{-- Estadísticas --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="zp-stat">
                <p class="zp-stat__label">Servicios activos</p>
                <p class="zp-stat__value">{{ $activeServices }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Próximo vencimiento</p>
                <p class="zp-stat__value">{{ $nextService?->ends_at?->format('d/m/Y') ?? '—' }}</p>
                <p class="mt-0.5 truncate text-xs text-muted">{{ $nextService?->name ?? 'Sin próximos' }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Facturas pendientes</p>
                <p class="zp-stat__value">{{ $pendingInvoices->count() }}</p>
                <p class="mt-0.5 text-xs text-muted">S/ {{ number_format($pendingInvoicesTotal, 2) }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Tickets abiertos</p>
                <p class="zp-stat__value">{{ $openTickets }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            {{-- Columna principal --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Tus servicios --}}
                <section class="ys-card !p-0 overflow-hidden">
                    <div class="flex items-center justify-between border-b border-line px-6 py-4">
                        <h2 class="font-bold text-ink">Tus servicios</h2>
                        <a href="{{ url('/panel/servicios') }}" class="ys-link !text-sm">Ver todos</a>
                    </div>

                    @forelse ($services as $service)
                        <a href="{{ route('panel.service', $service) }}"
                            class="flex items-center justify-between gap-4 border-b border-line px-6 py-4 transition last:border-0 hover:bg-brand-50/40">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{{ $service->name }}</p>
                                <p class="text-xs text-muted">
                                    {{ $service->domain?->name ? $service->domain->name.' · ' : '' }}
                                    Vence {{ $service->ends_at?->format('d/m/Y') ?? '—' }}
                                </p>
                            </div>
                            <x-status-badge :status="$service->status"/>
                        </a>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <p class="text-sm text-muted">Todavía no tienes servicios contratados.</p>
                            <a href="{{ url('/contratar') }}" class="ys-link mt-3 justify-center">Contratar un servicio →</a>
                        </div>
                    @endforelse
                </section>

                {{-- Más maneras de crecer --}}
                <section>
                    <h2 class="mb-4 font-bold text-ink">Más maneras de crecer</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($grow as $card)
                            <div class="ys-card flex flex-col">
                                <span class="ys-icon">
                                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $card['icon'] !!}</svg>
                                </span>
                                <h3 class="mt-4 font-bold text-ink">{{ $card['title'] }}</h3>
                                <p class="mt-1 flex-1 text-sm leading-relaxed text-muted">{{ $card['text'] }}</p>
                                <a href="{{ url('/contratar') }}" class="ys-btn ys-btn--ghost mt-4 self-start border border-line">
                                    {{ $card['cta'] }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- Panel derecho --}}
            <aside class="space-y-6">
                <section class="ys-card">
                    <h2 class="font-bold text-ink">Tu cuenta</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted">Titular</dt>
                            <dd class="font-medium text-ink">{{ $client->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Estado</dt>
                            <dd class="font-medium text-ink">{{ ucfirst($client->status) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Dominios por vencer</dt>
                            <dd class="font-medium text-ink">{{ $expiringDomains }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-line pt-3">
                            <dt class="text-muted">Saldo pendiente</dt>
                            <dd class="font-bold text-ink">S/ {{ number_format($pendingInvoicesTotal, 2) }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="ys-card">
                    <h2 class="mb-3 font-bold text-ink">Accesos rápidos</h2>
                    <div class="space-y-2.5">
                        <a href="{{ url('/panel/pagos') }}" class="zp-quick">
                            <span class="ys-icon !size-9 !rounded-lg">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/></svg>
                            </span>
                            Pagar una factura
                        </a>
                        <a href="{{ url('/panel/renovaciones') }}" class="zp-quick">
                            <span class="ys-icon !size-9 !rounded-lg">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
                            </span>
                            Ver qué vence pronto
                        </a>
                        <a href="{{ url('/client/tickets/create') }}" class="zp-quick">
                            <span class="ys-icon !size-9 !rounded-lg">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
                            </span>
                            Crear un ticket de soporte
                        </a>
                        <a href="{{ url('/client/billing-profile') }}" class="zp-quick">
                            <span class="ys-icon !size-9 !rounded-lg">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>
                            </span>
                            Datos de facturación
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-portal-shell>
