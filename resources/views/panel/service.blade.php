<x-portal-shell :client="$client" active="services" title="{{ $service->name }}">
    <div class="mx-auto max-w-4xl">
        {{-- Volver --}}
        <a href="{{ route('panel.services') }}" class="ys-link mb-4">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Volver a mis servicios
        </a>

        {{-- Cabecera --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="ys-eyebrow">{{ $service->product?->category ? ucfirst($service->product->category) : 'Servicio' }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ $service->name }}</h1>
                @if ($service->domain?->name)
                    <p class="mt-1 text-muted">{{ $service->domain->name }}</p>
                @endif
            </div>
            <x-status-badge :status="$service->status" class="!px-4 !py-1.5 !text-sm"/>
        </div>

        {{-- Resumen --}}
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="zp-stat">
                <p class="zp-stat__label">Precio</p>
                <p class="zp-stat__value zp-num">S/ {{ number_format((float) $service->price, 2) }}</p>
                <p class="mt-0.5 text-xs text-muted">{{ ucfirst($service->billing_cycle ?? '—') }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Contratado</p>
                <p class="zp-stat__value">{{ $service->starts_at?->format('d/m/Y') ?? '—' }}</p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Vence</p>
                <p @class(['zp-stat__value', 'text-red-600' => $service->ends_at?->isPast()])>
                    {{ $service->ends_at?->format('d/m/Y') ?? '—' }}
                </p>
            </div>
            <div class="zp-stat">
                <p class="zp-stat__label">Renovación</p>
                <p class="zp-stat__value">{{ $service->auto_renew ? 'Automática' : 'Manual' }}</p>
                @if ($service->next_renewal_at)
                    <p class="mt-0.5 text-xs text-muted">{{ $service->next_renewal_at->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>

        {{-- Accesos directos: panel, webmail, consola… --}}
        @if ($accesses->isNotEmpty())
            <section class="mt-8">
                <h2 class="font-bold text-ink">Entrar a tu servicio</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($accesses as $access)
                        @php $tag = $access->url ? 'a' : 'div'; @endphp
                        <{{ $tag }}
                            @if ($access->url) href="{{ $access->url }}" target="_blank" rel="noopener noreferrer" @endif
                            @class([
                                'flex items-start gap-4 rounded-2xl border border-line bg-white p-5 transition',
                                'hover:-translate-y-0.5 hover:border-brand-200' => (bool) $access->url,
                            ])
                            style="box-shadow: var(--shadow-card)">
                            <span class="ys-icon shrink-0">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">{!! $access->icon() !!}</svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="flex items-center gap-1.5 font-bold text-ink">
                                    {{ $access->displayLabel() }}
                                    @if ($access->url)
                                        <svg class="size-4 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14 21 3"/>
                                        </svg>
                                    @endif
                                </p>
                                @if ($access->username)
                                    <p class="mt-1.5 text-sm text-muted">
                                        Usuario: <span class="zp-key">{{ $access->username }}</span>
                                    </p>
                                @endif
                                @if ($access->instructions)
                                    <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $access->instructions }}</p>
                                @endif
                            </div>
                        </{{ $tag }}>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Lo que incluye el plan contratado --}}
        @if (! empty($planFeatures))
            <section class="ys-card mt-8 !p-0 overflow-hidden">
                <div class="border-b border-line px-7 py-4">
                    <h2 class="font-bold text-ink">Lo que incluye tu plan</h2>
                    @if ($service->product?->name)
                        <p class="mt-0.5 text-sm text-muted">{{ $service->product->name }}</p>
                    @endif
                </div>
                <dl class="grid gap-x-8 sm:grid-cols-2">
                    @foreach ($planFeatures as $feature)
                        <div class="flex items-center justify-between gap-4 border-b border-line px-7 py-3.5 text-sm">
                            <dt class="text-muted">{{ $feature['label'] }}</dt>
                            <dd class="text-right font-medium text-ink">{{ $feature['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        {{-- Consumo del plan --}}
        @if ($hosting?->hasUsageMetrics())
            <section class="mt-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-ink">Consumo de tu plan</h2>
                        @if ($hosting->usage_updated_at)
                            <p class="mt-0.5 text-sm text-muted">
                                Medido el {{ $hosting->usage_updated_at->format('d/m/Y \a \l\a\s H:i') }}
                            </p>
                        @endif
                    </div>
                </div>

                @if ($hosting->isNearCapacity())
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
                        <p class="flex items-center gap-2 text-sm font-medium text-red-700">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                            Estás llegando al límite de tu plan. Si se llena, tu sitio puede dejar de funcionar.
                        </p>
                        <a href="{{ url('/contratar') }}" class="ys-btn ys-btn--primary !py-2">Ampliar plan</a>
                    </div>
                @endif

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @if ($hosting->diskUsagePercent() !== null)
                        <x-usage-meter
                            label="Espacio en disco"
                            :percent="$hosting->diskUsagePercent()"
                            :used="\App\Models\Hosting::formatMegabytes($hosting->disk_used_mb)"
                            :limit="\App\Models\Hosting::formatMegabytes($hosting->disk_limit_mb)"/>
                    @endif

                    @if ($hosting->bandwidthUsagePercent() !== null)
                        <x-usage-meter
                            label="Transferencia"
                            :percent="$hosting->bandwidthUsagePercent()"
                            :used="\App\Models\Hosting::formatMegabytes($hosting->bandwidth_used_mb)"
                            :limit="\App\Models\Hosting::formatMegabytes($hosting->bandwidth_limit_mb)"/>
                    @endif

                    @if ($hosting->emailUsagePercent() !== null)
                        <x-usage-meter
                            label="Cuentas de correo"
                            :percent="$hosting->emailUsagePercent()"
                            :used="$hosting->email_accounts_used"
                            :limit="$hosting->email_accounts_limit"
                            unit="cuentas"/>
                    @endif

                    @if ($hosting->websitesUsagePercent() !== null)
                        <x-usage-meter
                            label="Sitios web"
                            :percent="$hosting->websitesUsagePercent()"
                            :used="$hosting->websites_used"
                            :limit="$hosting->websites_limit"
                            unit="sitios"/>
                    @endif

                    @if ($hosting->databasesUsagePercent() !== null)
                        <x-usage-meter
                            label="Bases de datos"
                            :percent="$hosting->databasesUsagePercent()"
                            :used="$hosting->databases_used"
                            :limit="$hosting->databases_limit_count"/>
                    @endif
                </div>
            </section>
        @endif

        {{-- Accesos de alojamiento --}}
        <section class="ys-card mt-6 !p-0 overflow-hidden">
            <div class="border-b border-line px-7 py-4">
                <h2 class="font-bold text-ink">Datos del servidor</h2>
                <p class="mt-0.5 text-sm text-muted">Lo que necesitas para conectarte o para dárselo a tu desarrollador.</p>
            </div>

            @if ($hosting)
                {{-- Solo datos de conexión: las cuotas ya salen arriba, en el
                     plan y en los medidores de consumo. --}}
                <dl class="divide-y divide-line">
                    @foreach ([
                        'Plan' => $hosting->plan,
                        'Servidor' => $hosting->server,
                        'IP del servidor' => $hosting->server_ip,
                        'Usuario del panel' => $hosting->directadmin_user,
                        'Centro de datos' => $hosting->datacenter,
                    ] as $label => $value)
                        <div class="flex items-center justify-between gap-4 px-7 py-3.5 text-sm">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-right font-medium text-ink">
                                @if (in_array($label, ['IP del servidor', 'Usuario del panel'], true) && $value)
                                    <span class="zp-key">{{ $value }}</span>
                                @else
                                    {{ $value !== null && $value !== '' ? $value : '—' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between gap-4 px-7 py-3.5 text-sm">
                        <dt class="text-muted">Estado del alojamiento</dt>
                        <dd><x-status-badge :status="$hosting->status"/></dd>
                    </div>
                </dl>

                <p class="border-t border-line bg-canvas px-7 py-4 text-xs text-muted">
                    ¿Necesitas tu contraseña o un acceso nuevo? Escríbenos por
                    <a href="{{ url('/client/tickets/create') }}" class="font-semibold text-brand-600 hover:text-brand-700">soporte</a>
                    — por seguridad no se muestran contraseñas aquí.
                </p>
            @else
                <div class="zp-empty">
                    <p class="text-sm text-muted">Este servicio no tiene datos de alojamiento asociados.</p>
                    <a href="{{ url('/client/tickets/create') }}" class="ys-link mt-3 justify-center">Consultar a soporte →</a>
                </div>
            @endif
        </section>

        {{-- Historial de renovaciones --}}
        @if ($service->renewals->isNotEmpty())
            <section class="ys-card mt-6 !p-0 overflow-hidden">
                <div class="border-b border-line px-7 py-4">
                    <h2 class="font-bold text-ink">Renovaciones</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="zp-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Nuevo vencimiento</th>
                                <th class="text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($service->renewals as $renewal)
                                <tr>
                                    <td class="text-muted">{{ $renewal->created_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-ink">{{ $renewal->new_ends_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="zp-num text-right font-medium">S/ {{ number_format((float) $renewal->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-portal-shell>
